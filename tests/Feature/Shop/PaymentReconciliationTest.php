<?php

namespace Tests\Feature\Shop;

use App\Actions\Checkout\CompleteCheckout;
use App\Enums\OrderStatus;
use App\Models\Order;
use App\Payments\CheckoutStatus;
use App\Payments\PaymentGateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\Fakes\FakePaymentGateway;
use Tests\TestCase;

/**
 * Order lifecycle safety: delayed payments, reconciliation with the provider,
 * amount checks and pruning that never deletes an order that could be paid.
 */
class PaymentReconciliationTest extends TestCase
{
    use RefreshDatabase;

    private FakePaymentGateway $gateway;

    protected function setUp(): void
    {
        parent::setUp();

        $this->gateway = new FakePaymentGateway;
        $this->app->instance(PaymentGateway::class, $this->gateway);
    }

    private function order(array $attributes = [], ?int $minutesAgo = null): Order
    {
        static $n = 0;
        $n++;

        $order = Order::create([
            'currency' => 'gbp', 'subtotal' => 5000, 'shipping' => 495, 'total' => 5495,
            'checkout_session_id' => "cs_test_{$n}",
            ...$attributes,
        ]);

        if ($minutesAgo !== null) {
            $order->forceFill(['created_at' => now()->subMinutes($minutesAgo)])->save();
        }

        return $order;
    }

    private function complete(Order $order, CheckoutStatus $status): bool
    {
        $this->gateway->status = $status;

        return app(CompleteCheckout::class)->handle($order, $this->gateway->retrieveCheckout($order->checkout_session_id));
    }

    // Delayed payments --------------------------------------------------------

    public function test_a_delayed_payment_moves_through_processing_to_paid()
    {
        $order = $this->order();

        $this->assertTrue($this->complete($order, CheckoutStatus::Processing));
        $this->assertSame(OrderStatus::Processing, $order->status);
        $this->assertSame('customer@example.com', $order->email);
        $this->assertNull($order->paid_at);

        $this->assertTrue($this->complete($order, CheckoutStatus::Paid));
        $this->assertSame(OrderStatus::Paid, $order->status);
        $this->assertNotNull($order->paid_at);
    }

    public function test_a_processing_order_can_fail_but_is_never_cancelled_as_expired()
    {
        $order = $this->order(['status' => OrderStatus::Processing]);

        $this->assertFalse($this->complete($order, CheckoutStatus::Expired));
        $this->assertSame(OrderStatus::Processing, $order->status);

        $this->assertTrue(app(CompleteCheckout::class)->fail($order));
        $this->assertSame(OrderStatus::Failed, $order->status);
    }

    // Amount checks -------------------------------------------------------------

    public function test_the_amount_paid_is_recorded_separately_from_the_order_total()
    {
        $this->gateway->amountTotal = 5495;
        $order = $this->order();

        $this->complete($order, CheckoutStatus::Paid);

        $this->assertSame(5495, $order->amount_paid);
        $this->assertSame(5495, $order->total);
        $this->assertNull($order->review_reason);
    }

    public function test_a_mismatched_amount_is_recorded_as_paid_but_flagged_for_review()
    {
        Log::spy();
        $this->gateway->amountTotal = 4995;
        $order = $this->order();

        $this->complete($order, CheckoutStatus::Paid);

        $this->assertTrue($order->isPaid());
        $this->assertSame(4995, $order->amount_paid);
        $this->assertSame(5495, $order->total, 'The order total must not be overwritten.');
        $this->assertStringContainsString('differs from order total', $order->review_reason);
        Log::shouldHaveReceived('warning')->once()->withArgs(fn ($message, $context) => $context['order'] === $order->reference);
    }

    // Reconciliation job --------------------------------------------------------

    public function test_reconciliation_settles_orders_whose_webhook_never_arrived()
    {
        $paid = $this->order(minutesAgo: 60);
        $expired = $this->order(minutesAgo: 60 * 25);
        $processing = $this->order(minutesAgo: 60);
        $stillOpen = $this->order(minutesAgo: 60);
        $tooRecent = $this->order(minutesAgo: 5);

        $this->gateway->statusBySession = [
            $paid->checkout_session_id => CheckoutStatus::Paid,
            $expired->checkout_session_id => CheckoutStatus::Expired,
            $processing->checkout_session_id => CheckoutStatus::Processing,
            $stillOpen->checkout_session_id => CheckoutStatus::Pending,
            $tooRecent->checkout_session_id => CheckoutStatus::Paid,
        ];

        $this->artisan('shop:reconcile-checkouts')
            ->expectsOutputToContain('Checked 4 orders: 3 updated, 0 errors.')
            ->assertSuccessful();

        $this->assertSame(OrderStatus::Paid, $paid->refresh()->status);
        $this->assertSame(OrderStatus::Cancelled, $expired->refresh()->status);
        $this->assertSame(OrderStatus::Processing, $processing->refresh()->status);
        $this->assertSame(OrderStatus::Pending, $stillOpen->refresh()->status);
        $this->assertSame(OrderStatus::Pending, $tooRecent->refresh()->status);
    }

    public function test_reconciliation_rechecks_processing_orders_until_they_settle()
    {
        $order = $this->order(['status' => OrderStatus::Processing], minutesAgo: 60 * 72);
        $this->gateway->status = CheckoutStatus::Paid;

        $this->artisan('shop:reconcile-checkouts')->assertSuccessful();

        $this->assertTrue($order->refresh()->isPaid());
    }

    public function test_one_provider_error_does_not_stop_the_rest_and_fails_the_run()
    {
        $broken = $this->order(minutesAgo: 60);
        $fine = $this->order(minutesAgo: 60);
        $this->gateway->failRetrieveFor = [$broken->checkout_session_id];

        $this->artisan('shop:reconcile-checkouts')
            ->expectsOutputToContain('1 updated, 1 errors')
            ->assertFailed();

        $this->assertSame(OrderStatus::Pending, $broken->refresh()->status);
        $this->assertTrue($fine->refresh()->isPaid());
    }

    public function test_settled_orders_are_left_alone()
    {
        $this->order(['status' => OrderStatus::Paid], minutesAgo: 60);
        $this->order(['status' => OrderStatus::Cancelled], minutesAgo: 60);

        $this->artisan('shop:reconcile-checkouts')->expectsOutputToContain('Checked 0 orders')->assertSuccessful();
    }

    // Pruning -------------------------------------------------------------------

    public function test_pruning_only_deletes_long_cancelled_orders()
    {
        $oldCancelled = $this->order(['status' => OrderStatus::Cancelled], minutesAgo: 60 * 24 * 31);
        $recentCancelled = $this->order(['status' => OrderStatus::Cancelled], minutesAgo: 60 * 24 * 5);
        $oldPending = $this->order(minutesAgo: 60 * 24 * 31);
        $oldProcessing = $this->order(['status' => OrderStatus::Processing], minutesAgo: 60 * 24 * 31);
        $oldPaid = $this->order(['status' => OrderStatus::Paid], minutesAgo: 60 * 24 * 31);

        $this->artisan('model:prune', ['--model' => [Order::class]])->assertSuccessful();

        $this->assertModelMissing($oldCancelled);
        $this->assertModelExists($recentCancelled);
        $this->assertModelExists($oldPending);
        $this->assertModelExists($oldProcessing);
        $this->assertModelExists($oldPaid);
    }
}
