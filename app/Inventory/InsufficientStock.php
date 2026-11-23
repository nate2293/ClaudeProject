<?php

namespace App\Inventory;

use RuntimeException;

/**
 * Thrown when an order can't be reserved because a line has less stock than requested.
 */
class InsufficientStock extends RuntimeException
{
    /** @param  list<array{name: string, colour: string, size: string, requested: int, available: int}>  $shortages */
    public function __construct(public readonly array $shortages)
    {
        parent::__construct('Not enough stock for: '.implode(', ', array_map(
            fn (array $line) => "{$line['name']} ({$line['colour']}, {$line['size']})",
            $shortages,
        )));
    }

    /** A sentence for the shopper, e.g. "Merino Jumper (Oat, M) has only 1 left." */
    public function customerMessage(): string
    {
        return collect($this->shortages)->map(fn (array $line) => $line['available'] === 0
            ? "{$line['name']} ({$line['colour']}, {$line['size']}) has just sold out."
            : "{$line['name']} ({$line['colour']}, {$line['size']}) has only {$line['available']} left.",
        )->implode(' ').' Please update your bag and try again.';
    }
}
