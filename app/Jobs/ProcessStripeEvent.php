<?php

namespace App\Jobs;

use App\Actions\Checkout\CompleteCheckout;
use App\Actions\Orders\RecordDispute;
use App\Actions\Orders\RecordRefund;
use App\Models\Order;
use App\Models\StripeEvent;
use App\Payments\PaymentException;
use App\Payments\PaymentGateway;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Applies a stored Stripe webhook event (checkout outcome, refund or dispute) to
 * its order, off the request path.
 *
 * Provider errors are retried with backoff. Running twice, or alongside the
 * success page and the reconciliation job, is safe: CompleteCheckout only ever
 * makes each status change once.
 */
class ProcessStripeEvent implements ShouldQueue
{
    use Queueable;

    public int $tries = 8;

    /** Seconds before a stuck attempt is killed; must stay below the queue's retry_after (90). */
    public int $timeout = 30;

    public function __construct(public readonly string $eventId) {}

    /** @return list<int> Seconds to wait before each retry (about 1h45m in total). */
    public function backoff(): array
    {
        return [10, 30, 60, 300, 900, 1800, 3600];
    }

    public function handle(
        PaymentGateway $gateway,
        CompleteCheckout $completeCheckout,
        RecordRefund $recordRefund,
        RecordDispute $recordDispute,
    ): void {
        $event = StripeEvent::find($this->eventId);

        if (! $event || $event->isProcessed()) {
            return;
        }

        $event->increment('attempts');

        try {
            match (true) {
                str_starts_with($event->type, 'checkout.session.') => $this->applyCheckout($event, $gateway, $completeCheckout),
                $event->type === 'charge.refunded' => $this->applyRefund($event, $recordRefund),
                str_starts_with($event->type, 'charge.dispute.') => $this->applyDispute($event, $recordDispute),
            };
        } catch (PaymentException $e) {
            $event->forceFill(['last_error' => $e->getMessage()])->save();

            throw $e; // Retried with backoff.
        }

        $event->forceFill(['processed_at' => now(), 'last_error' => null])->save();
    }

    public function failed(Throwable $e): void
    {
        // The order isn't lost: the reconciliation job keeps checking it with Stripe.
        Log::error('Stripe event could not be processed after retries.', [
            'event_id' => $this->eventId,
            'error' => $e->getMessage(),
        ]);
    }

    /**
     * Refund totals are cumulative, so applying an older or repeated event is harmless.
     * Refunds for unknown payments (e.g. another integration) are ignored.
     */
    private function applyRefund(StripeEvent $event, RecordRefund $recordRefund): void
    {
        $charge = $event->object();
        $order = isset($charge['payment_intent']) ? Order::firstWhere('payment_reference', $charge['payment_intent']) : null;

        if ($order) {
            $recordRefund->handle($order, (int) $charge['amount_refunded']);
        }
    }

    private function applyDispute(StripeEvent $event, RecordDispute $recordDispute): void
    {
        $dispute = $event->object();

        $recordDispute->handle([
            'id' => $dispute['id'],
            'amount' => (int) $dispute['amount'],
            'currency' => $dispute['currency'],
            'reason' => $dispute['reason'] ?? null,
            'status' => $dispute['status'],
            'payment_reference' => $dispute['payment_intent'] ?? null,
            'evidence_due_by' => $dispute['evidence_details']['due_by'] ?? null,
        ]);
    }

    private function applyCheckout(StripeEvent $event, PaymentGateway $gateway, CompleteCheckout $completeCheckout): void
    {
        $session = $event->object();
        $order = Order::where('checkout_session_id', $event->object_id)->first();

        if (! $order) {
            $this->reportUnknownSession($event, $session);

            return;
        }

        if ($event->type === 'checkout.session.async_payment_failed') {
            $completeCheckout->fail($order);

            return;
        }

        // Re-fetch from the API instead of trusting the event's snapshot, which
        // may be stale or arrive out of order.
        $completeCheckout->handle($order, $gateway->retrieveCheckout($event->object_id));
    }

    /**
     * A session with our order metadata but no matching order means money may
     * have moved for an order we've lost: log it as critical for a person to
     * investigate. Sessions without our metadata belong to another integration
     * on the same Stripe account and are ignored.
     *
     * @param  array<string, mixed>  $session
     */
    private function reportUnknownSession(StripeEvent $event, array $session): void
    {
        $reference = $session['metadata']['order_reference'] ?? null;

        if ($reference) {
            Log::critical('Stripe event for a checkout session with no matching order.', [
                'event_id' => $event->id,
                'event_type' => $event->type,
                'session_id' => $event->object_id,
                'order_reference' => $reference,
                'payment_status' => $session['payment_status'] ?? null,
            ]);
        }
    }
}
