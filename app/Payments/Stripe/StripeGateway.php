<?php

namespace App\Payments\Stripe;

use App\Models\Order;
use App\Models\OrderItem;
use App\Payments\CheckoutResult;
use App\Payments\CheckoutSession;
use App\Payments\CheckoutStatus;
use App\Payments\PaymentException;
use App\Payments\PaymentGateway;
use Stripe\Exception\ApiErrorException;
use Stripe\StripeClient;

/**
 * Stripe Checkout (hosted payment page) implementation of the payment gateway.
 *
 * @see https://docs.stripe.com/payments/checkout
 */
class StripeGateway implements PaymentGateway
{
    public function __construct(private readonly ?StripeClient $stripe) {}

    public function createCheckout(Order $order): CheckoutSession
    {
        $currency = $order->currency;

        $params = [
            'mode' => 'payment',
            'client_reference_id' => $order->reference,
            'metadata' => ['order_id' => $order->id, 'order_reference' => $order->reference],
            'line_items' => $order->items->map(fn (OrderItem $item) => [
                'quantity' => $item->quantity,
                'price_data' => [
                    'currency' => $currency,
                    'unit_amount' => $item->unit_price,
                    'product_data' => [
                        'name' => $item->product_name,
                        'description' => "Colour: {$item->colour} · Size: {$item->size}",
                    ],
                ],
            ])->all(),
            'shipping_address_collection' => [
                'allowed_countries' => config('shop.shipping.allowed_countries'),
            ],
            'shipping_options' => [[
                'shipping_rate_data' => [
                    'type' => 'fixed_amount',
                    'display_name' => $order->shipping === 0 ? 'Free UK delivery' : 'Standard UK delivery',
                    'fixed_amount' => ['amount' => $order->shipping, 'currency' => $currency],
                    'delivery_estimate' => [
                        'minimum' => ['unit' => 'business_day', 'value' => 2],
                        'maximum' => ['unit' => 'business_day', 'value' => 4],
                    ],
                ],
            ]],
            // The stock reservation lasts as long as the payment page. Stripe's minimum
            // is 30 minutes from creation; the extra minute absorbs clock differences.
            'expires_at' => now()->addMinutes(max(30, (int) config('shop.stock.reservation_minutes')) + 1)->getTimestamp(),
            'success_url' => route('checkout.success').'?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => route('cart.index'),
        ];

        if ($order->email) {
            $params['customer_email'] = $order->email;
        }

        $session = $this->call(fn (StripeClient $stripe) => $stripe->checkout->sessions->create(
            $params,
            ['idempotency_key' => "checkout-{$order->reference}"],
        ));

        return new CheckoutSession(id: $session->id, url: $session->url);
    }

    public function retrieveCheckout(string $sessionId): CheckoutResult
    {
        $session = $this->call(fn (StripeClient $stripe) => $stripe->checkout->sessions->retrieve($sessionId))->toArray();

        $status = match (true) {
            $session['status'] === 'expired' => CheckoutStatus::Expired,
            in_array($session['payment_status'], ['paid', 'no_payment_required'], true) => CheckoutStatus::Paid,
            $session['status'] === 'complete' => CheckoutStatus::Processing,
            default => CheckoutStatus::Pending,
        };

        // Newer API versions moved shipping details under `collected_information`.
        $shipping = $session['collected_information']['shipping_details'] ?? $session['shipping_details'] ?? null;
        $paymentIntent = $session['payment_intent'] ?? null;

        return new CheckoutResult(
            sessionId: $session['id'],
            status: $status,
            email: $session['customer_details']['email'] ?? null,
            customerName: $shipping['name'] ?? $session['customer_details']['name'] ?? null,
            shippingAddress: $shipping['address'] ?? null,
            paymentReference: is_array($paymentIntent) ? $paymentIntent['id'] : $paymentIntent,
            amountTotal: $session['amount_total'] ?? null,
            amountShipping: $session['total_details']['amount_shipping'] ?? null,
        );
    }

    public function expireCheckout(string $sessionId): void
    {
        $this->call(fn (StripeClient $stripe) => $stripe->checkout->sessions->expire($sessionId));
    }

    /**
     * @template T
     *
     * @param  callable(StripeClient): T  $callback
     * @return T
     */
    private function call(callable $callback): mixed
    {
        if ($this->stripe === null) {
            throw PaymentException::notConfigured();
        }

        try {
            return $callback($this->stripe);
        } catch (ApiErrorException $e) {
            throw PaymentException::providerError($e);
        }
    }
}
