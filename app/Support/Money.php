<?php

namespace App\Support;

use Illuminate\Support\Number;

/** Formats minor-unit amounts the same way as the storefront's formatPrice(). */
final class Money
{
    public static function format(int $minor, ?string $currency = null): string
    {
        $currency = strtoupper($currency ?? config('shop.currency'));
        $precision = $minor % 100 === 0 ? 0 : 2;

        return Number::currency($minor / 100, $currency, 'en_GB', $precision);
    }
}
