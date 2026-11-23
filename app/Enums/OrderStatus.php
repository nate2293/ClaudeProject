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

    /** Paid, then part of the payment was refunded. */
    case PartiallyRefunded = 'partially_refunded';

    /** Paid, then refunded in full. */
    case Refunded = 'refunded';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Awaiting payment',
            self::Processing => 'Payment processing',
            self::Paid => 'Paid',
            self::Failed => 'Payment failed',
            self::Cancelled => 'Cancelled',
            self::PartiallyRefunded => 'Partly refunded',
            self::Refunded => 'Refunded',
        };
    }

    /** Statuses of orders that were paid for (whether or not money was later refunded). */
    public static function paidFor(): array
    {
        return [self::Paid, self::PartiallyRefunded, self::Refunded];
    }

    /** Statuses whose outcome is still unknown and must be settled with the payment provider. */
    public static function unsettled(): array
    {
        return [self::Pending, self::Processing];
    }
}
