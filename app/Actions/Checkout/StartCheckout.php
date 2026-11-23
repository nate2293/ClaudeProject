<?php

namespace App\Actions\Checkout;

use App\Cart\Cart;
use App\Cart\CartLine;
use App\Enums\OrderStatus;
use App\Inventory\InsufficientStock;
use App\Inventory\Inventory;
use App\Models\Order;
use App\Models\User;
use App\Payments\PaymentException;
use App\Payments\PaymentGateway;
use App\Support\Vat;
use Illuminate\Support\Facades\DB;

/**
 * Turns the current bag into a pending order, reserves its stock, and opens a
 * hosted checkout session for it. The session expires after the reservation
 * window, which cancels the order and releases the stock.
 */
class StartCheckout
{
    public function __construct(
        private readonly PaymentGateway $gateway,
        private readonly CompleteCheckout $completeCheckout,
        private readonly Inventory $inventory,
    ) {}

    /**
     * @param  Order|null  $replacing  This browser's previous unpaid order, if any. Its
     *                                 checkout session is closed so an out-of-date bag
     *                                 can't be paid for, and the order is cancelled.
     *
     * @throws InsufficientStock When a line has less stock than the bag asks for; nothing is created.
     * @throws PaymentException
     */
    public function handle(Cart $cart, ?User $user = null, ?Order $replacing = null): StartedCheckout
    {
        if ($replacing) {
            $this->closePrevious($replacing);
        }

        $order = DB::transaction(function () use ($cart, $user) {
            $vatRate = (int) config('shop.vat.rate');

            $order = Order::create([
                'user_id' => $user?->id,
                'email' => $user?->email,
                'currency' => config('shop.currency'),
                'subtotal' => $cart->subtotal(),
                'shipping' => $cart->shipping(),
                'total' => $cart->total(),
                'vat_rate' => $vatRate,
                'vat_amount' => Vat::included($cart->total(), $vatRate),
            ]);

            $order->items()->createMany($cart->lines()->map(fn (CartLine $line) => [
                'product_id' => $line->product->id,
                'stock_level_id' => $line->stockLevelId,
                'product_name' => $line->product->name,
                'colour' => $line->colour,
                'size' => $line->size,
                'unit_price' => $line->unitPrice(),
                'quantity' => $line->quantity,
                'line_total' => $line->total(),
            ])->values()->all());

            // All or nothing: a shortage rolls back the order as well.
            $this->inventory->reserve($order);

            return $order;
        });

        try {
            $session = $this->gateway->createCheckout($order->load('items'));
        } catch (PaymentException $e) {
            DB::transaction(function () use ($order) {
                $this->inventory->release($order);
                $order->delete();
            });

            throw $e;
        }

        $order->update(['checkout_session_id' => $session->id]);

        return new StartedCheckout($order, $session->url);
    }

    private function closePrevious(Order $order): void
    {
        if ($order->status !== OrderStatus::Pending || ! $order->checkout_session_id) {
            return;
        }

        try {
            $this->gateway->expireCheckout($order->checkout_session_id);
        } catch (PaymentException $e) {
            // Most likely the customer completed it moments ago; leave the order
            // for the webhook or success page to settle rather than cancelling it.
            report($e);

            return;
        }

        $this->completeCheckout->cancel($order);
    }
}
