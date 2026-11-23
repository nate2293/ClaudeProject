<?php

namespace Tests\Feature\Account;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use App\Payments\PaymentGateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\CreatesOrders;
use Tests\Fakes\FakePaymentGateway;
use Tests\TestCase;

class AccountOrdersTest extends TestCase
{
    use CreatesOrders, RefreshDatabase;

    private User $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app->instance(PaymentGateway::class, new FakePaymentGateway);
        $this->customer = User::factory()->create(['email' => 'jane@example.com']);
    }

    private function orderFor(?User $user, array $attributes = []): Order
    {
        $order = $this->paidOrder();
        $order->forceFill(['user_id' => $user?->id, ...$attributes])->save();

        return $order->refresh();
    }

    public function test_customers_see_their_paid_orders_newest_first_and_not_abandoned_checkouts()
    {
        $older = $this->orderFor($this->customer, ['created_at' => now()->subWeek()]);
        $newer = $this->orderFor($this->customer);
        $abandoned = $this->placeOrder();
        $abandoned->forceFill(['user_id' => $this->customer->id, 'status' => OrderStatus::Cancelled])->save();
        $someoneElses = $this->orderFor(User::factory()->create());

        $this->actingAs($this->customer)->get(route('account.orders'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('account/orders/index')
                ->has('orders.data', 2)
                ->where('orders.data.0.reference', $newer->reference)
                ->where('orders.data.1.reference', $older->reference)
                ->where('orders.data.0.progress.title', 'Order confirmed')
                ->where('orders.data.0.progress.step', 1)
                ->has('orders.data.0.items.0.swatch'));
    }

    public function test_guest_orders_with_the_same_email_appear_once_the_email_is_verified()
    {
        $guestOrder = $this->orderFor(null, ['email' => 'jane@example.com']);

        $this->customer->forceFill(['email_verified_at' => null])->save();
        $this->actingAs($this->customer)->get(route('account.orders'))->assertInertia(fn (Assert $page) => $page->has('orders.data', 0));
        $this->get(route('account.orders.show', $guestOrder->reference))->assertNotFound();

        $this->customer->forceFill(['email_verified_at' => now()])->save();
        $this->get(route('account.orders'))->assertInertia(fn (Assert $page) => $page->has('orders.data', 1));
    }

    public function test_an_order_page_shows_details_but_other_peoples_orders_are_not_found()
    {
        $mine = $this->orderFor($this->customer);
        $theirs = $this->orderFor(User::factory()->create());

        $this->actingAs($this->customer)->get(route('account.orders.show', $mine->reference))
            ->assertInertia(fn (Assert $page) => $page
                ->component('account/orders/show')
                ->where('order.reference', $mine->reference)
                ->where('order.deliverTo.lines.0', '1 Test Street')
                ->where('order.vat.rate', '20%'));

        $this->get(route('account.orders.show', $theirs->reference))->assertNotFound();
        $this->get(route('account.orders.show', 'LL-NOPE0000'))->assertNotFound();
    }

    public function test_progress_follows_the_order_through_shipping_with_tracking_and_return_date()
    {
        $order = $this->orderFor($this->customer, [
            'fulfilment_status' => 'shipped', 'shipped_at' => now()->setDate(2026, 11, 20),
            'carrier' => 'Royal Mail', 'tracking_number' => 'RM1GB', 'tracking_url' => 'https://track.example/RM1GB',
        ]);

        $this->actingAs($this->customer)->get(route('account.orders.show', $order->reference))
            ->assertInertia(fn (Assert $page) => $page
                ->where('order.progress.title', 'On its way')
                ->where('order.progress.step', 3)
                ->where('order.tracking.url', 'https://track.example/RM1GB')
                ->where('order.returnBy', fn (string $date) => str_starts_with($date, '2026-12-20')));
    }

    public function test_delayed_payments_and_refunds_are_explained()
    {
        $processing = $this->placeOrder();
        $processing->forceFill(['user_id' => $this->customer->id, 'status' => OrderStatus::Processing])->save();
        $refunded = $this->orderFor($this->customer, ['status' => OrderStatus::Refunded, 'refunded_at' => now(), 'amount_refunded' => 6495]);

        $this->actingAs($this->customer)->get(route('account.orders'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('orders.data', fn ($orders) => collect($orders)->firstWhere('reference', $processing->reference)['progress']['title'] === 'Payment processing'
                    && collect($orders)->firstWhere('reference', $refunded->reference)['progress']['title'] === 'Refunded'));
    }

    public function test_the_account_needs_signing_in()
    {
        $this->get(route('account.orders'))->assertRedirect(route('login'));
    }
}
