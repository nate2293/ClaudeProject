<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Currency
    |--------------------------------------------------------------------------
    |
    | All prices are stored as integers in the currency's minor unit (pence).
    |
    */

    'currency' => env('SHOP_CURRENCY', 'gbp'),

    /*
    |--------------------------------------------------------------------------
    | Shipping
    |--------------------------------------------------------------------------
    */

    'shipping' => [
        'free_threshold' => 7500,
        'standard_rate' => 495,
        'allowed_countries' => ['GB'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Catalogue & cart
    |--------------------------------------------------------------------------
    */

    'products_per_page' => 12,

    'max_quantity_per_line' => 10,

    /*
    |--------------------------------------------------------------------------
    | Search
    |--------------------------------------------------------------------------
    |
    | With a search engine (SCOUT_DRIVER=meilisearch), at most this many matches
    | are returned for one search before filters and pagination apply.
    |
    */

    'search' => [
        'max_results' => 500,
    ],

    /*
    |--------------------------------------------------------------------------
    | Stock
    |--------------------------------------------------------------------------
    |
    | Stock is reserved when checkout starts and held until the payment page
    | expires (Stripe allows 30 minutes to 24 hours), after which the order is
    | cancelled and the units return to sale. Delayed payments (e.g. Bacs) keep
    | their reservation until they settle. Variants at or below the low-stock
    | threshold show "Only N left".
    |
    */

    'stock' => [
        'reservation_minutes' => (int) env('SHOP_RESERVATION_MINUTES', 30),
        'low_stock_threshold' => 3,
    ],

    /*
    |--------------------------------------------------------------------------
    | Product media
    |--------------------------------------------------------------------------
    |
    | Filesystem disk holding product photos. A product's `image` is a path on
    | this disk (e.g. "products/merino-jumper.jpg"); full URLs are used as-is.
    | Use "public" locally (run `php artisan storage:link`) and "s3" in
    | production, with AWS_URL pointing at the CDN in front of the bucket.
    |
    */

    'media_disk' => env('SHOP_MEDIA_DISK', 'public'),

    /*
    |--------------------------------------------------------------------------
    | Scheduler heartbeats
    |--------------------------------------------------------------------------
    |
    | URLs pinged after each successful run of a scheduled job (e.g. from
    | Better Stack, Healthchecks.io or Cronitor). The monitor alerts when a
    | ping stops arriving, which catches a stopped cron as well as failures.
    |
    */

    'heartbeats' => [
        'reconcile_checkouts' => env('HEARTBEAT_RECONCILE_CHECKOUTS_URL'),
        'prune_orders' => env('HEARTBEAT_PRUNE_ORDERS_URL'),
    ],

];
