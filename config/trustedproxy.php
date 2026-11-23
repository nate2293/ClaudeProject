<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Trusted proxies
    |--------------------------------------------------------------------------
    |
    | Behind a load balancer or CDN, the app must trust the proxy's
    | X-Forwarded-* headers to see the visitor's real IP (used by rate limits)
    | and that the request was HTTPS (used for URLs and secure cookies).
    | Set TRUSTED_PROXIES to the proxy IPs/CIDRs, comma-separated, or "*" when
    | the app is only reachable through the proxy. Laravel Cloud, Forge and
    | Vapor are detected automatically.
    |
    */

    'proxies' => env('TRUSTED_PROXIES') === '*'
        ? '*'
        : (array_values(array_filter(array_map('trim', explode(',', (string) env('TRUSTED_PROXIES'))))) ?: null),

];
