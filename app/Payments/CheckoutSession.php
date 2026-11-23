<?php

namespace App\Payments;

/** A newly created hosted checkout session that the customer is redirected to. */
final readonly class CheckoutSession
{
    public function __construct(
        public string $id,
        public string $url,
    ) {}
}
