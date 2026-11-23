<?php

namespace App\Auth;

use Illuminate\Http\Request;

/**
 * Where to send someone after they sign in: back to the shop page they came
 * from. Only paths on this site are accepted, so a crafted sign-in link can't
 * bounce people to another website.
 */
final class ReturnUrl
{
    /** Pages it makes no sense to return to. */
    private const EXCLUDED_PREFIXES = ['/login', '/register', '/forgot-password', '/reset-password', '/auth/', '/logout', '/admin', '/livewire'];

    /** Remember `?return=/some/page` from the request as the post-sign-in destination. */
    public static function rememberFrom(Request $request): void
    {
        $path = self::sanitise($request->query('return'));

        if ($path !== null) {
            $request->session()->put('url.intended', url($path));
        }
    }

    public static function sanitise(mixed $value): ?string
    {
        if (! is_string($value) || $value === '' || strlen($value) > 2000) {
            return null;
        }

        // A single leading slash, and no scheme or host: "//evil.com" and "/\evil.com" are rejected.
        if (! str_starts_with($value, '/') || str_starts_with($value, '//') || str_contains($value, '\\') || preg_match('/[\x00-\x1F]/', $value)) {
            return null;
        }

        foreach (self::EXCLUDED_PREFIXES as $prefix) {
            if (str_starts_with($value, $prefix)) {
                return null;
            }
        }

        return $value;
    }
}
