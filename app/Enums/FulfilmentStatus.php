<?php

namespace App\Enums;

/** Where a paid order is in the warehouse. Tracked separately from payment. */
enum FulfilmentStatus: string
{
    case Unfulfilled = 'unfulfilled';
    case Packed = 'packed';
    case Shipped = 'shipped';

    public function label(): string
    {
        return match ($this) {
            self::Unfulfilled => 'To pack',
            self::Packed => 'Packed',
            self::Shipped => 'Shipped',
        };
    }
}
