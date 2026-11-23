<?php

namespace App\Actions\Checkout;

use App\Models\Order;

/** Result of starting checkout: the pending order and where to send the customer. */
final readonly class StartedCheckout
{
    public function __construct(
        public Order $order,
        public string $redirectUrl,
    ) {}
}
