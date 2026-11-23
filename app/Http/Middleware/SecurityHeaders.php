<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use Symfony\Component\HttpFoundation\Response;

/**
 * Browser security headers.
 *
 * The storefront gets a strict Content Security Policy: scripts only from this
 * site, with a per-request nonce for the one inline script (Ziggy's routes).
 * The admin area (Filament/Livewire, which needs inline scripts and eval) gets
 * every header except the CSP. While Vite's dev server is running the CSP is
 * skipped too, as hot reloading injects its own scripts.
 */
class SecurityHeaders
{
    /** Paths served by Filament/Livewire rather than the Inertia storefront. */
    private const NO_CSP_PATHS = ['admin', 'admin/*', 'livewire/*', 'filament/*'];

    public function handle(Request $request, Closure $next): Response
    {
        $useCsp = ! $request->is(self::NO_CSP_PATHS) && ! Vite::isRunningHot();

        if ($useCsp) {
            Vite::useCspNonce();
        }

        $response = $next($request);

        $headers = $response->headers;
        $headers->set('X-Content-Type-Options', 'nosniff');
        $headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $headers->set('X-Frame-Options', 'DENY');
        $headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=(), usb=()');
        $headers->set('Cross-Origin-Opener-Policy', 'same-origin');

        // HSTS only over HTTPS in production: browsers then refuse plain HTTP to this
        // host for a year, which would lock out a local or staging HTTP setup.
        if ($request->isSecure() && app()->isProduction()) {
            $headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        if ($useCsp && ! $headers->has('Content-Security-Policy')) {
            $headers->set('Content-Security-Policy', $this->policy(Vite::cspNonce()));
        }

        return $response;
    }

    private function policy(string $nonce): string
    {
        // Product photos may come from a CDN (AWS_URL) or another media host.
        $media = collect([config('filesystems.disks.s3.url'), config('filesystems.disks.public.url')])
            ->filter()
            ->map(fn (string $url) => parse_url($url, PHP_URL_SCHEME).'://'.parse_url($url, PHP_URL_HOST))
            ->unique()
            ->implode(' ');

        $assets = config('app.asset_url') ? ' '.rtrim((string) config('app.asset_url'), '/') : '';

        return implode('; ', [
            "default-src 'self'",
            "script-src 'self' 'nonce-{$nonce}'{$assets}",
            // React sets inline style attributes (e.g. colour swatches).
            "style-src 'self' 'unsafe-inline'{$assets}",
            "img-src 'self' data: blob: {$media}",
            "font-src 'self' data:{$assets}",
            "connect-src 'self'",
            "frame-ancestors 'none'",
            "base-uri 'self'",
            "object-src 'none'",
            // Checkout sends the browser on to Stripe's hosted payment page.
            "form-action 'self' https://checkout.stripe.com",
        ]);
    }
}
