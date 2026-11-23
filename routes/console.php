<?php

use App\Models\NewsletterSubscriber;
use App\Models\Order;
use App\Models\StripeEvent;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Both need the scheduler: run `php artisan schedule:run` every minute via cron in production.
// onOneServer() means only one machine runs each job when several run cron; it needs a
// shared cache (Redis). Heartbeat URLs are pinged only after a successful run.

// Settle pending/processing orders with Stripe, in case a webhook was missed.
$reconcileHeartbeat = config('shop.heartbeats.reconcile_checkouts');
Schedule::command('shop:reconcile-checkouts')
    ->everyFifteenMinutes()
    ->withoutOverlapping()
    ->onOneServer()
    ->pingOnSuccessIf(filled($reconcileHeartbeat), (string) $reconcileHeartbeat);

// Warn (via the QueueBusy listener in AppServiceProvider) when jobs pile up, e.g.
// because no queue worker is running, so webhook processing doesn't stall unnoticed.
Schedule::command('queue:monitor', ['default', '--max' => 100])
    ->everyFiveMinutes()
    ->onOneServer();

// Delete long-cancelled orders and old processed Stripe events (see each model's
// prunable()). Pending orders and unprocessed events are never pruned.
$pruneHeartbeat = config('shop.heartbeats.prune_orders');
Schedule::command('model:prune', ['--model' => [Order::class, StripeEvent::class, NewsletterSubscriber::class]])
    ->daily()
    ->onOneServer()
    ->pingOnSuccessIf(filled($pruneHeartbeat), (string) $pruneHeartbeat);
