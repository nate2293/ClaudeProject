<?php

namespace App\Enums;

enum OrderStatus: string
{
    /** Checkout started; the customer hasn't finished on the payment page. */
    case Pending = 'pending';

    /** Checkout finished, but a delayed payment method (e.g. Bacs Direct Debit) hasn't settled. */
    case Processing = 'processing';

    case Paid = 'paid';
    case Failed = 'failed';

    /** The checkout session expired without payment. */
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Awaiting payment',
            self::Processing => 'Payment processing',
            self::Paid => 'Paid',
            self::Failed => 'Payment failed',
            self::Cancelled => 'Cancelled',
        };
    }

    /** Statuses whose outcome is still unknown and must be settled with the payment provider. */
    public static function unsettled(): array
    {
        return [self::Pending, self::Processing];
    }
}
