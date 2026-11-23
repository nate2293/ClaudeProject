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
 * Receives Stripe webhooks for checkouts, refunds and disputes: verifies the signature, records the event
 * and queues it for processing, then replies straight away. Stripe expects a
 * quick 2xx, and no Stripe API call happens while it waits.
 *
 * @see https://docs.stripe.com/checkout/fulfillment
 * @see ProcessStripeEvent
 */
class StripeWebhookController extends Controller
{
    /**
     * Handled event types, each with the fields of its object kept with the event:
     * everything needed to process and audit it, but no customer details (those
     * are fetched from the API when a checkout is applied).
     */
    private const HANDLED_EVENTS = [
        'checkout.session.completed' => self::SESSION_FIELDS,
        'checkout.session.async_payment_succeeded' => self::SESSION_FIELDS,
        'checkout.session.async_payment_failed' => self::SESSION_FIELDS,
        'checkout.session.expired' => self::SESSION_FIELDS,
        'charge.refunded' => ['id', 'object', 'amount', 'amount_refunded', 'currency', 'payment_intent', 'refunded'],
        'charge.dispute.created' => self::DISPUTE_FIELDS,
        'charge.dispute.updated' => self::DISPUTE_FIELDS,
        'charge.dispute.closed' => self::DISPUTE_FIELDS,
    ];

    private const SESSION_FIELDS = [
        'id', 'object', 'status', 'payment_status', 'amount_total', 'currency',
        'client_reference_id', 'payment_intent', 'metadata',
    ];

    private const DISPUTE_FIELDS = ['id', 'object', 'amount', 'currency', 'reason', 'status', 'payment_intent', 'charge', 'evidence_details'];

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

        if (! array_key_exists($event->type, self::HANDLED_EVENTS)) {
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
        $object = Arr::only($event->data->object->toArray(), self::HANDLED_EVENTS[$event->type]);

        if (isset($object['evidence_details'])) {
            $object['evidence_details'] = Arr::only($object['evidence_details'], ['due_by']);
        }

        return [
            'type' => $event->type,
            'object_id' => $object['id'] ?? null,
            'payload' => [
                'id' => $event->id,
                'type' => $event->type,
                'created' => $event->created,
                'livemode' => $event->livemode,
                'data' => ['object' => $object],
            ],
        ];
    }
}
