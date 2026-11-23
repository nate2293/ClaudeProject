<?php

namespace App\Actions\Checkout;

use App\Enums\OrderStatus;
use App\Inventory\Inventory;
use App\Models\Order;
use App\Payments\CheckoutResult;
use App\Payments\CheckoutStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Applies the outcome of a checkout session to its order.
 *
 *   pending ──(customer finishes, delayed method)──▶ processing ──▶ paid / failed
 *      │  └──────────────(card, settled at once)──────────────────▶ paid
 *      └──(session expires unpaid)──▶ cancelled
 *
 * Safe to call from the success redirect, the webhook and the reconciliation job,
 * in any order and even at the same moment: each change is one conditional update,
 * and only the call that made it returns `true`. Stock is settled in the same
 * transaction as that change (paid → sold, cancelled/failed → released), so it
 * happens exactly once. Hang future side effects (confirmation emails) on the
 * return value too.
 */
class CompleteCheckout
{
    public function __construct(private readonly Inventory $inventory) {}

    /** @return bool Whether this call changed the order's status. */
    public function handle(Order $order, CheckoutResult $result): bool
    {
        return match ($result->status) {
            CheckoutStatus::Paid => $this->markPaid($order, $result),
            CheckoutStatus::Processing => $order->transition([OrderStatus::Pending], [
                'status' => OrderStatus::Processing,
                ...$this->customerDetails($order, $result),
            ]),
            CheckoutStatus::Expired => $this->cancel($order),
            CheckoutStatus::Pending => false,
        };
    }

    /** @return bool Whether this call changed the order's status. */
    public function fail(Order $order): bool
    {
        return $this->settle($order, OrderStatus::unsettled(), ['status' => OrderStatus::Failed], fn () => $this->inventory->release($order));
    }

    /**
     * Only an order whose customer never finished checkout can be cancelled; a
     * processing order has a payment in flight and must wait for its outcome.
     *
     * @return bool Whether this call changed the order's status.
     */
    public function cancel(Order $order): bool
    {
        return $this->settle($order, [OrderStatus::Pending], ['status' => OrderStatus::Cancelled], fn () => $this->inventory->release($order));
    }

    private function markPaid(Order $order, CheckoutResult $result): bool
    {
        $amountPaid = $result->amountTotal ?? $order->total;
        $mismatch = $amountPaid !== $order->total;

        $oversold = [];
        $changed = $this->settle($order, OrderStatus::unsettled(), [
            'status' => OrderStatus::Paid,
            'paid_at' => now(),
            'amount_paid' => $amountPaid,
            'review_reason' => $mismatch
                ? "Amount paid ({$amountPaid}) differs from order total ({$order->total})."
                : null,
            ...$this->customerDetails($order, $result),
        ], function () use ($order, &$oversold) {
            $oversold = $this->inventory->commit($order);
        });

        if ($oversold) {
            Log::warning('Order paid for more units than are on hand; stock is now negative.', [
                'order' => $order->reference,
                'items' => array_map(fn ($item) => "{$item->product_name} ({$item->colour}, {$item->size})", $oversold),
            ]);
        }

        // The money has been taken either way, so the order is recorded as paid,
        // but a mismatch means our totals and the charge disagree: flag it loudly.
        if ($changed && $mismatch) {
            Log::warning('Order paid with an unexpected amount; flagged for review.', [
                'order' => $order->reference,
                'order_total' => $order->total,
                'amount_paid' => $amountPaid,
                'currency' => $order->currency,
            ]);
        }

        return $changed;
    }

    /**
     * Change the status and, only if this call made the change, apply its stock
     * effect, both in one transaction.
     *
     * @param  list<OrderStatus>  $from
     * @param  array<string, mixed>  $attributes
     */
    private function settle(Order $order, array $from, array $attributes, callable $stockEffect): bool
    {
        return DB::transaction(function () use ($order, $from, $attributes, $stockEffect) {
            $changed = $order->transition($from, $attributes);

            if ($changed) {
                $stockEffect();
            }

            return $changed;
        });
    }

    /** @return array<string, mixed> */
    private function customerDetails(Order $order, CheckoutResult $result): array
    {
        return [
            'email' => $result->email ?? $order->email,
            'customer_name' => $result->customerName ?? $order->customer_name,
            'shipping_address' => $result->shippingAddress ?? $order->shipping_address,
            'payment_reference' => $result->paymentReference ?? $order->payment_reference,
        ];
    }
}
