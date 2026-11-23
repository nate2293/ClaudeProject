<?php

namespace App\Console\Commands;

use App\Actions\Checkout\CompleteCheckout;
use App\Models\Order;
use App\Payments\PaymentException;
use App\Payments\PaymentGateway;
use Illuminate\Console\Command;

/**
 * Settles orders whose outcome we haven't heard about, by asking the payment
 * provider directly. This covers webhooks that never arrived (not configured,
 * an outage, retries exhausted) and customers who closed the tab before the
 * success page loaded, so the webhook becomes a speed-up, not the only source
 * of truth. Abandoned checkouts are cancelled once their session has expired.
 */
class ReconcileCheckouts extends Command
{
    protected $signature = 'shop:reconcile-checkouts
        {--older-than=30 : Only check orders created at least this many minutes ago}
        {--limit=200 : Maximum number of orders to check in one run}';

    protected $description = 'Sync pending and processing orders with the payment provider';

    public function handle(PaymentGateway $gateway, CompleteCheckout $completeCheckout): int
    {
        $orders = Order::query()
            ->unsettled()
            ->whereNotNull('checkout_session_id')
            ->where('created_at', '<=', now()->subMinutes((int) $this->option('older-than')))
            ->oldest()
            ->limit((int) $this->option('limit'))
            ->get();

        $changed = 0;
        $errors = 0;

        foreach ($orders as $order) {
            try {
                if ($completeCheckout->handle($order, $gateway->retrieveCheckout($order->checkout_session_id))) {
                    $changed++;
                    $this->line("{$order->reference}: {$order->status->label()}");
                }
            } catch (PaymentException $e) {
                // Keep going: one bad session shouldn't stop the rest settling.
                $errors++;
                report($e);
                $this->warn("{$order->reference}: {$e->getMessage()}");
            }
        }

        $this->info("Checked {$orders->count()} orders: {$changed} updated, {$errors} errors.");

        // A non-zero exit lets scheduler monitoring flag runs with provider errors.
        return $errors > 0 ? self::FAILURE : self::SUCCESS;
    }
}
