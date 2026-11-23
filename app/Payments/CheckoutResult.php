<?php

namespace App\Payments;

/** Provider-agnostic snapshot of a checkout session's outcome. */
final readonly class CheckoutResult
{
    /**
     * @param  array<string, string|null>|null  $shippingAddress
     */
    public function __construct(
        public string $sessionId,
        public CheckoutStatus $status,
        public ?string $email = null,
        public ?string $customerName = null,
        public ?array $shippingAddress = null,
        public ?string $paymentReference = null,
        public ?int $amountTotal = null,
        public ?int $amountShipping = null,
    ) {}
}
