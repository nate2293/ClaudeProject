<?php

namespace App\Providers;

use App\Cart\Cart;
use App\Http\Middleware\EnsureSessionIsCurrent;
use App\Payments\PaymentGateway;
use App\Payments\Stripe\StripeGateway;
use App\Search\DatabaseProductSearch;
use App\Search\ProductSearch;
use App\Search\ScoutProductSearch;
use Illuminate\Auth\Events\Login;
use Illuminate\Foundation\Events\DiagnosingHealth;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Queue\Events\QueueBusy;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;
use Stripe\StripeClient;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // One cart instance per request, backed by the current session.
        $this->app->scoped(Cart::class, fn ($app) => new Cart($app['session.store']));

        // Database search unless a search engine is configured (SCOUT_DRIVER=meilisearch).
        $this->app->scoped(ProductSearch::class, fn ($app) => config('scout.driver') === 'null'
            ? $app->make(DatabaseProductSearch::class)
            : new ScoutProductSearch($app->make(DatabaseProductSearch::class), (int) config('shop.search.max_results')));

        $this->app->bind(PaymentGateway::class, function () {
            $secret = config('services.stripe.secret');

            return new StripeGateway(filled($secret) ? new StripeClient($secret) : null);
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Pass resources to Inertia as plain objects rather than `{ data: ... }`.
        JsonResource::withoutWrapping();

        // Make `/up` check the services every request depends on, so an uptime
        // monitor notices a lost database or Redis connection, not just PHP being up.
        Event::listen(DiagnosingHealth::class, function () {
            DB::select('select 1');
            Cache::get('health-check');
        });

        // Record when this session signed in, so revoking an account's sessions works on any store.
        Event::listen(Login::class, fn () => session()->put(EnsureSessionIsCurrent::SESSION_KEY, time()));

        // Raised by the scheduled `queue:monitor` when a queue backs up.
        Event::listen(QueueBusy::class, fn (QueueBusy $event) => Log::warning('Queue backlog: jobs are waiting longer than expected.', [
            'connection' => $event->connection,
            'queue' => $event->queue,
            'size' => $event->size,
        ]));
    }
}
