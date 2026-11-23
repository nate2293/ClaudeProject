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
}
