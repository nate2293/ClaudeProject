<?php

namespace App\Filament\Support;

/** Links to the Stripe dashboard, in test or live mode to match the configured key. */
final class StripeDashboard
{
    public static function payment(string $paymentIntentId): string
    {
        return self::base().'/payments/'.$paymentIntentId;
    }

    public static function dispute(string $disputeId): string
    {
        return self::base().'/disputes/'.$disputeId;
    }

    private static function base(): string
    {
        $test = str_contains((string) config('services.stripe.secret'), '_test_');

        return 'https://dashboard.stripe.com'.($test ? '/test' : '');
    }
}
