<?php

namespace Tests\Feature\Shop;

use App\Enums\OrderStatus;
use App\Jobs\ProcessStripeEvent;
use App\Models\Order;
use App\Models\StripeEvent;
use App\Payments\CheckoutStatus;
use App\Payments\PaymentException;
use App\Payments\PaymentGateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Tests\Fakes\FakePaymentGateway;
use Tests\TestCase;

class StripeWebhookTest extends TestCase
{
    use RefreshDatabase;

    private const SECRET = 'whsec_test_secret';

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.stripe.webhook_secret' => self::SECRET]);
        $this->app->instance(PaymentGateway::class, new FakePaymentGateway);
    }

    private function pendingOrder(): Order
    {
        return Order::create([
            'currency' => 'gbp',
            'subtotal' => 5000,
            'shipping' => 495,
            'total' => 5495,
            'checkout_session_id' => 'cs_test_abc',
        ]);
    }

    /** Send a webhook signed the same way Stripe does (HMAC-SHA256 over "timestamp.payload"). */
    private function sendWebhook(string $type, string $sessionId, ?string $secret = self::SECRET, array $metadata = [], ?string $eventId = null)
    {
        $payload = json_encode([
            'id' => $eventId ?? 'evt_'.Str::random(16),
            'object' => 'event',
            'type' => $type,
            'data' => ['object' => ['id' => $sessionId, 'object' => 'checkout.session', 'payment_status' => 'paid', 'metadata' => (object) $metadata,
                'customer_details' => ['email' => 'jane@example.com', 'name' => 'Jane Customer']]],
        ]);

        $timestamp = time();
        $signature = hash_hmac('sha256', "{$timestamp}.{$payload}", $secret);

        return $this->call('POST', route('stripe.webhook'), [], [], [], [
            'HTTP_STRIPE_SIGNATURE' => "t={$timestamp},v1={$signature}",
            'CONTENT_TYPE' => 'application/json',
        ], $payload);
    }

    public function test_completed_checkout_marks_the_order_paid()
    {
        $order = $this->pendingOrder();

        $this->sendWebhook('checkout.session.completed', 'cs_test_abc')->assertOk();

        $order->refresh();
        $this->assertSame(OrderStatus::Paid, $order->status);
        $this->assertSame('Jane Customer', $order->customer_name);
        $this->assertSame('London', $order->shipping_address['city']);
    }

    public function test_webhooks_do_not_start_a_session()
    {
        $this->pendingOrder();

        $response = $this->sendWebhook('checkout.session.completed', 'cs_test_abc')->assertOk();

        $this->assertEmpty($response->headers->getCookies());
    }

    public function test_duplicate_events_are_handled_idempotently()
    {
        $order = $this->pendingOrder();

        $this->sendWebhook('checkout.session.completed', 'cs_test_abc', eventId: 'evt_same')->assertOk();
        $paidAt = $order->refresh()->paid_at;

        // Stripe redelivers the same event, and also sends a different event for the same session.
        $this->travel(5)->minutes();
        Queue::fake();
        $this->sendWebhook('checkout.session.completed', 'cs_test_abc', eventId: 'evt_same')->assertOk();
        Queue::assertNothingPushed();

        Queue::fake()->serializeAndRestore(false);
        $this->sendWebhook('checkout.session.completed', 'cs_test_abc', eventId: 'evt_other')->assertOk();
        Queue::assertPushed(ProcessStripeEvent::class, 1);

        $this->assertDatabaseCount('stripe_events', 2);
        $this->assertEquals($paidAt, $order->refresh()->paid_at);
    }

    public function test_async_payment_failure_marks_the_order_failed()
    {
        $order = $this->pendingOrder();

        $this->sendWebhook('checkout.session.async_payment_failed', 'cs_test_abc')->assertOk();

        $this->assertSame(OrderStatus::Failed, $order->refresh()->status);
    }

    public function test_invalid_signatures_are_rejected()
    {
        $order = $this->pendingOrder();

        $this->sendWebhook('checkout.session.completed', 'cs_test_abc', secret: 'whsec_wrong')->assertStatus(400);

        $this->assertSame(OrderStatus::Pending, $order->refresh()->status);
    }

    public function test_a_payment_for_a_missing_order_of_ours_is_logged_as_critical()
    {
        Log::spy();

        $this->sendWebhook('checkout.session.async_payment_succeeded', 'cs_gone', metadata: ['order_reference' => 'LL-LOST1234'])
            ->assertOk();

        Log::shouldHaveReceived('critical')->once()->withArgs(fn ($message, $context) => $context['order_reference'] === 'LL-LOST1234');
    }

    public function test_sessions_from_other_integrations_are_acknowledged_quietly()
    {
        Log::spy();

        $this->sendWebhook('checkout.session.completed', 'cs_someone_else')->assertOk();

        Log::shouldNotHaveReceived('critical');
    }

    public function test_a_delayed_payment_settles_through_webhooks()
    {
        $gateway = new FakePaymentGateway;
        $gateway->status = CheckoutStatus::Processing;
        $this->app->instance(PaymentGateway::class, $gateway);
        $order = $this->pendingOrder();

        $this->sendWebhook('checkout.session.completed', 'cs_test_abc')->assertOk();
        $this->assertSame(OrderStatus::Processing, $order->refresh()->status);

        $gateway->status = CheckoutStatus::Paid;
        $this->sendWebhook('checkout.session.async_payment_succeeded', 'cs_test_abc')->assertOk();
        $this->assertSame(OrderStatus::Paid, $order->refresh()->status);
    }

    public function test_unknown_sessions_and_unhandled_events_are_acknowledged()
    {
        $this->sendWebhook('checkout.session.completed', 'cs_not_ours')->assertOk();
        $this->sendWebhook('customer.created', 'cus_123')->assertOk();
    }

    // Queued processing ---------------------------------------------------------

    public function test_events_are_recorded_and_queued_without_calling_stripe()
    {
        Queue::fake();
        $gateway = new FakePaymentGateway;
        $this->app->instance(PaymentGateway::class, $gateway);
        $order = $this->pendingOrder();

        $this->sendWebhook('checkout.session.completed', 'cs_test_abc', metadata: ['order_reference' => $order->reference], eventId: 'evt_1')
            ->assertOk();

        Queue::assertPushed(ProcessStripeEvent::class, fn ($job) => $job->eventId === 'evt_1');
        $this->assertSame(0, $gateway->retrievals, 'No Stripe API call while Stripe waits for the response.');
        $this->assertSame(OrderStatus::Pending, $order->refresh()->status);

        $event = StripeEvent::findOrFail('evt_1');
        $this->assertSame('checkout.session.completed', $event->type);
        $this->assertSame('cs_test_abc', $event->object_id);
        $this->assertSame($order->reference, $event->object()['metadata']['order_reference']);
        $this->assertArrayNotHasKey('customer_details', $event->object(), 'Customer details are not stored with events.');
        $this->assertNull($event->processed_at);
    }

    public function test_the_job_applies_the_event_and_marks_it_processed()
    {
        Queue::fake();
        $order = $this->pendingOrder();
        $this->sendWebhook('checkout.session.completed', 'cs_test_abc', eventId: 'evt_1');

        app()->call([new ProcessStripeEvent('evt_1'), 'handle']);

        $this->assertTrue($order->refresh()->isPaid());
        $event = StripeEvent::findOrFail('evt_1');
        $this->assertNotNull($event->processed_at);
        $this->assertSame(1, $event->attempts);
    }

    public function test_provider_errors_are_recorded_and_retried()
    {
        Queue::fake();
        $gateway = new FakePaymentGateway;
        $gateway->failRetrieveFor = ['cs_test_abc'];
        $this->app->instance(PaymentGateway::class, $gateway);
        $order = $this->pendingOrder();
        $this->sendWebhook('checkout.session.completed', 'cs_test_abc', eventId: 'evt_1');

        try {
            app()->call([new ProcessStripeEvent('evt_1'), 'handle']);
            $this->fail('The job should rethrow so the queue retries it.');
        } catch (PaymentException) {
        }

        $event = StripeEvent::findOrFail('evt_1');
        $this->assertNull($event->processed_at);
        $this->assertStringContainsString('could not retrieve', $event->last_error);

        // Stripe redelivers while it's still unprocessed: it is queued again.
        $this->sendWebhook('checkout.session.completed', 'cs_test_abc', eventId: 'evt_1');
        Queue::assertPushed(ProcessStripeEvent::class, 2);

        $gateway->failRetrieveFor = [];
        app()->call([new ProcessStripeEvent('evt_1'), 'handle']);

        $this->assertTrue($order->refresh()->isPaid());
        $this->assertSame(2, $event->refresh()->attempts);
        $this->assertNull($event->last_error);
    }

    public function test_a_job_that_runs_out_of_retries_is_logged()
    {
        Log::spy();

        (new ProcessStripeEvent('evt_1'))->failed(new PaymentException('Stripe is down'));

        Log::shouldHaveReceived('error')->once()->withArgs(fn ($message, $context) => $context['event_id'] === 'evt_1');
    }

    public function test_only_long_processed_events_are_pruned()
    {
        Queue::fake();
        foreach (['evt_old', 'evt_recent', 'evt_stuck'] as $id) {
            $this->sendWebhook('checkout.session.completed', 'cs_x', eventId: $id);
        }
        StripeEvent::whereKey('evt_old')->update(['processed_at' => now()->subDays(91)]);
        StripeEvent::whereKey('evt_recent')->update(['processed_at' => now()->subDays(5)]);
        StripeEvent::whereKey('evt_stuck')->update(['created_at' => now()->subDays(200)]);

        $this->artisan('model:prune', ['--model' => [StripeEvent::class]])->assertSuccessful();

        $this->assertSame(['evt_recent', 'evt_stuck'], StripeEvent::orderBy('id')->pluck('id')->all());
    }
}
