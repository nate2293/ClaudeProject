<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessStripeEvent;
use App\Models\StripeEvent;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Stripe\Event;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;
use UnexpectedValueException;

/**
 * Receives Stripe Checkout webhooks: verifies the signature, records the event
 * and queues it for processing, then replies straight away. Stripe expects a
 * quick 2xx, and no Stripe API call happens while it waits.
 *
 * @see https://docs.stripe.com/checkout/fulfillment
 * @see ProcessStripeEvent
 */
class StripeWebhookController extends Controller
{
    private const HANDLED_EVENTS = [
        'checkout.session.completed',
        'checkout.session.async_payment_succeeded',
        'checkout.session.async_payment_failed',
        'checkout.session.expired',
    ];

    /**
     * Session fields kept with the event. Everything needed to process and audit
     * it, but no customer details (those are fetched from the API when applied).
     */
    private const STORED_SESSION_FIELDS = [
        'id', 'object', 'status', 'payment_status', 'amount_total', 'currency',
        'client_reference_id', 'payment_intent', 'metadata',
    ];

    public function __invoke(Request $request): Response
    {
        $secret = config('services.stripe.webhook_secret');

        if (blank($secret)) {
            Log::error('Stripe webhook received but STRIPE_WEBHOOK_SECRET is not set.');

            return response('Webhook not configured', 500);
        }

        try {
            $event = Webhook::constructEvent(
                $request->getContent(),
                (string) $request->header('Stripe-Signature'),
                $secret,
            );
        } catch (SignatureVerificationException|UnexpectedValueException) {
            return response('Invalid payload', 400);
        }

        if (! in_array($event->type, self::HANDLED_EVENTS, true)) {
            return response('Ignored', 200);
        }

        // Stripe redelivers the same event ID on retries: the primary key makes
        // this one row however many times it arrives.
        $stored = StripeEvent::createOrFirst(['id' => $event->id], $this->attributes($event));

        // Re-queue anything not yet processed. A redelivery then also recovers an
        // event whose job was lost; processing twice is harmless.
        if (! $stored->isProcessed()) {
            ProcessStripeEvent::dispatch($stored->id);
        }

        return response('Accepted', 200);
    }

    /** @return array<string, mixed> */
    private function attributes(Event $event): array
    {
        $session = Arr::only($event->data->object->toArray(), self::STORED_SESSION_FIELDS);

        return [
            'type' => $event->type,
            'object_id' => $session['id'] ?? null,
            'payload' => [
                'id' => $event->id,
                'type' => $event->type,
                'created' => $event->created,
                'livemode' => $event->livemode,
                'data' => ['object' => $session],
            ],
        ];
    }
}
