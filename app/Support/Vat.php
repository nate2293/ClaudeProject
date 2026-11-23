<?php

namespace App\Support;

/**
 * VAT arithmetic for VAT-inclusive prices, in minor units.
 */
final class Vat
{
    /**
     * VAT contained in a VAT-inclusive amount, e.g. £120.00 at 20% (2000 bps) contains £20.00.
     *
     * @param  int  $rateBasisPoints  2000 = 20%
     */
    public static function included(int $gross, int $rateBasisPoints): int
    {
        if ($rateBasisPoints <= 0) {
            return 0;
        }

        return (int) round($gross * $rateBasisPoints / (10000 + $rateBasisPoints));
    }

    /** "20%" for 2000 basis points; "17.5%" for 1750. */
    public static function label(int $rateBasisPoints): string
    {
        return rtrim(rtrim(number_format($rateBasisPoints / 100, 2), '0'), '.').'%';
    }
}
