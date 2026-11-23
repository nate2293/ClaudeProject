<?php

namespace Tests\Feature\Shop;

use App\Enums\OrderStatus;
use App\Mail\OrderRefunded;
use App\Models\Order;
use App\Models\PaymentDispute;
use App\Payments\PaymentGateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\Concerns\CreatesOrders;
use Tests\Fakes\FakePaymentGateway;
use Tests\TestCase;

/** Refunds and disputes made in the Stripe dashboard reach the shop by webhook. */
class RefundsAndDisputesTest extends TestCase
{
    use CreatesOrders, RefreshDatabase;

    private const SECRET = 'whsec_test_secret';

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.stripe.webhook_secret' => self::SECRET]);
        $this->app->instance(PaymentGateway::class, new FakePaymentGateway);
        Mail::fake();
    }

    private function sendEvent(string $type, array $object, ?string $eventId = null)
    {
        $payload = json_encode([
            'id' => $eventId ?? 'evt_'.Str::random(16), 'object' => 'event', 'type' => $type,
            'created' => time(), 'livemode' => false, 'data' => ['object' => $object],
        ]);
        $timestamp = time();
        $signature = hash_hmac('sha256', "{$timestamp}.{$payload}", self::SECRET);

        return $this->call('POST', route('stripe.webhook'), [], [], [], [
            'HTTP_STRIPE_SIGNATURE' => "t={$timestamp},v1={$signature}", 'CONTENT_TYPE' => 'application/json',
        ], $payload);
    }

    private function refundEvent(Order $order, int $totalRefunded, ?string $eventId = null)
    {
        return $this->sendEvent('charge.refunded', [
            'id' => 'ch_1', 'object' => 'charge', 'amount' => $order->amount_paid, 'amount_refunded' => $totalRefunded,
            'currency' => 'gbp', 'payment_intent' => $order->payment_reference, 'refunded' => $totalRefunded >= $order->amount_paid,
        ], $eventId);
    }

    public function test_partial_then_full_refunds_update_the_order_and_email_each_amount()
    {
        $order = $this->paidOrder(); // £64.95 paid

        $this->refundEvent($order, 2000)->assertOk();
        $order->refresh();
        $this->assertSame(OrderStatus::PartiallyRefunded, $order->status);
        $this->assertSame(2000, $order->amount_refunded);
        $this->assertNull($order->refunded_at);

        $this->refundEvent($order, 6495)->assertOk();
        $order->refresh();
        $this->assertSame(OrderStatus::Refunded, $order->status);
        $this->assertNotNull($order->refunded_at);

        Mail::assertQueued(OrderRefunded::class, 2);
        Mail::assertQueued(OrderRefunded::class, fn ($mail) => $mail->amount === 2000);
        Mail::assertQueued(OrderRefunded::class, fn ($mail) => $mail->amount === 4495);
    }

    public function test_repeated_and_out_of_order_refund_events_change_nothing()
    {
        $order = $this->paidOrder();

        $this->refundEvent($order, 3000, 'evt_a');
        $this->refundEvent($order, 3000, 'evt_a'); // redelivered
        $this->refundEvent($order, 1000, 'evt_old'); // an older event arriving late

        $this->assertSame(3000, $order->refresh()->amount_refunded);
        Mail::assertQueued(OrderRefunded::class, 1);
    }

    public function test_refunds_for_payments_we_do_not_know_are_ignored()
    {
        $this->sendEvent('charge.refunded', ['id' => 'ch_x', 'object' => 'charge', 'amount' => 100, 'amount_refunded' => 100, 'currency' => 'gbp', 'payment_intent' => 'pi_other'])
            ->assertOk();

        Mail::assertNothingQueued();
    }

    public function test_a_dispute_is_recorded_flagged_and_alerted_then_closed()
    {
        Log::spy();
        $order = $this->paidOrder();
        $dispute = [
            'id' => 'dp_1', 'object' => 'dispute', 'amount' => 6495, 'currency' => 'gbp', 'reason' => 'fraudulent',
            'status' => 'needs_response', 'payment_intent' => $order->payment_reference, 'charge' => 'ch_1',
            'evidence_details' => ['due_by' => now()->addDays(7)->timestamp, 'has_evidence' => false],
        ];

        $this->sendEvent('charge.dispute.created', $dispute)->assertOk();

        $record = PaymentDispute::findOrFail('dp_1');
        $this->assertTrue($record->order->is($order));
        $this->assertTrue($record->isOpen());
        $this->assertNotNull($record->evidence_due_by);
        $this->assertStringContainsString('disputed (fraudulent)', $order->refresh()->review_reason);
        Log::shouldHaveReceived('critical')->once()->withArgs(fn ($message, $context) => $context['dispute'] === 'dp_1');

        $this->sendEvent('charge.dispute.closed', [...$dispute, 'status' => 'won'])->assertOk();

        $this->assertSame('won', $record->refresh()->status);
        $this->assertNotNull($record->closed_at);
        Log::shouldHaveReceived('critical')->once(); // Not again for the update.
    }
}
