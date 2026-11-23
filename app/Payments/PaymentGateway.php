<?php

namespace App\Payments;

use App\Models\Order;

/**
 * A hosted-checkout payment provider.
 *
 * The application talks to payments only through this contract, so the provider
 * (Stripe today) can be swapped or faked in tests without touching controllers.
 */
interface PaymentGateway
{
    /**
     * Create a hosted checkout session for a pending order.
     *
     * @throws PaymentException
     */
    public function createCheckout(Order $order): CheckoutSession;

    /**
     * Fetch the latest state of a checkout session from the provider.
     *
     * @throws PaymentException
     */
    public function retrieveCheckout(string $sessionId): CheckoutResult;

    /**
     * Close an open checkout session so it can no longer be paid.
     *
     * @throws PaymentException If the session can't be expired (e.g. it was already completed).
     */
    public function expireCheckout(string $sessionId): void;

    /**
     * Refund part or all of a payment.
     *
     * @param  string  $paymentReference  The payment's provider reference (Stripe: the PaymentIntent ID).
     * @param  int  $amount  Amount to refund now, in minor units.
     * @param  string  $idempotencyKey  Same key, same refund: a retried request can't refund twice.
     * @return int Total refunded on the payment so far, including this refund.
     *
     * @throws PaymentException
     */
    public function refund(string $paymentReference, int $amount, string $idempotencyKey): int;
}
