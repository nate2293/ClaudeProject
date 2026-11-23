<?php

use App\Http\Middleware\EnsureSessionIsCurrent;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Support\Facades\Route;
use Sentry\Laravel\Integration;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            Route::group([], base_path('routes/webhooks.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Every response, including errors.
        $middleware->append(SecurityHeaders::class);

        // Signed-in visitors opening /login or /register go back to the shop.
        $middleware->redirectUsersTo('/');

        $middleware->web(append: [
            EnsureSessionIsCurrent::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Reported exceptions go to Sentry when SENTRY_LARAVEL_DSN is set.
        Integration::handles($exceptions);
    })->create();
