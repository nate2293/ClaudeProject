<?php

namespace Tests\Feature\Shop;

use App\Actions\Checkout\CompleteCheckout;
use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Payments\CheckoutStatus;
use App\Payments\PaymentGateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Fakes\FakePaymentGateway;
use Tests\TestCase;

class CheckoutTest extends TestCase
{
    use RefreshDatabase;

    private FakePaymentGateway $gateway;

    protected function setUp(): void
    {
        parent::setUp();

        $this->gateway = new FakePaymentGateway;
        $this->app->instance(PaymentGateway::class, $this->gateway);
    }

    private function fillBag(int $price = 3000, int $quantity = 2): Product
    {
        $product = Product::factory()->create(['price' => $price]);

        $this->post(route('cart.lines.store'), ['product_id' => $product->id, 'colour' => 'Black', 'size' => 'M', 'quantity' => $quantity]);

        return $product;
    }

    public function test_checkout_creates_a_pending_order_and_redirects_to_the_payment_page()
    {
        $product = $this->fillBag();

        $response = $this->post(route('checkout.store'), [], ['X-Inertia' => 'true']);

        $order = Order::with('items')->sole();
        $response->assertStatus(409)->assertHeader('X-Inertia-Location', "https://checkout.stripe.test/{$order->reference}");

        $this->assertSame(OrderStatus::Pending, $order->status);
        $this->assertSame(6000, $order->subtotal);
        $this->assertSame(495, $order->shipping);
        $this->assertSame(6495, $order->total);
        $this->assertSame("cs_test_{$order->reference}", $order->checkout_session_id);
        $this->assertSame($product->id, $order->items->sole()->product_id);
        $this->assertSame(2, $order->items->sole()->quantity);
    }

    public function test_signed_in_customers_are_attached_to_the_order()
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $this->fillBag();

        $this->post(route('checkout.store'));

        $this->assertSame($user->id, Order::sole()->user_id);
        $this->assertSame($user->email, Order::sole()->email);
    }

    public function test_checkout_with_an_empty_bag_redirects_back_to_the_bag()
    {
        $this->post(route('checkout.store'))->assertRedirect(route('cart.index'))->assertSessionHas('error');

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_a_payment_provider_failure_discards_the_order_and_keeps_the_bag()
    {
        $this->gateway->failOnCreate = true;
        $this->fillBag();

        $this->from(route('cart.index'))->post(route('checkout.store'))->assertRedirect(route('cart.index'))->assertSessionHas('error');

        $this->assertDatabaseCount('orders', 0);
        $this->get(route('cart.index'))->assertInertia(fn (Assert $page) => $page->has('cart.lines', 1));
    }

    public function test_success_page_marks_the_order_paid_and_empties_the_bag()
    {
        $this->fillBag();
        $this->post(route('checkout.store'));
        $order = Order::sole();

        $this->get(route('checkout.success', ['session_id' => $order->checkout_session_id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('checkout/success')
                ->where('order.reference', $order->reference)
                ->where('order.status', 'paid')
                ->has('order.items', 1)
                ->where('cart.count', 0));

        $order->refresh();
        $this->assertTrue($order->isPaid());
        $this->assertNotNull($order->paid_at);
        $this->assertSame('customer@example.com', $order->email);
        $this->assertSame('pi_test_123', $order->payment_reference);
    }

    public function test_success_page_for_an_expired_session_returns_to_the_bag()
    {
        $this->gateway->status = CheckoutStatus::Expired;
        $this->fillBag();
        $this->post(route('checkout.store'));

        $this->get(route('checkout.success', ['session_id' => Order::sole()->checkout_session_id]))
            ->assertRedirect(route('cart.index'));

        $this->assertSame(OrderStatus::Cancelled, Order::sole()->status);
    }

    public function test_success_page_requires_a_known_session()
    {
        $this->get(route('checkout.success'))->assertNotFound();
        $this->get(route('checkout.success', ['session_id' => 'cs_unknown']))->assertNotFound();
    }

    public function test_starting_checkout_again_closes_the_previous_session_and_cancels_its_order()
    {
        $this->fillBag();
        $this->post(route('checkout.store'));
        $first = Order::sole();

        $this->post(route('checkout.store'));

        $this->assertSame([$first->checkout_session_id], $this->gateway->expired);
        $this->assertSame(OrderStatus::Cancelled, $first->refresh()->status);
        $this->assertSame(1, Order::pending()->count());
    }

    public function test_a_previous_session_that_cannot_be_expired_is_left_for_the_webhook()
    {
        $this->fillBag();
        $this->post(route('checkout.store'));
        $first = Order::sole();

        $this->gateway->failOnExpire = true;
        $this->post(route('checkout.store'));

        $this->assertSame(OrderStatus::Pending, $first->refresh()->status);
    }

    public function test_the_bag_is_kept_while_a_delayed_payment_is_processing()
    {
        $this->gateway->status = CheckoutStatus::Processing;
        $this->fillBag();
        $this->post(route('checkout.store'));

        $this->get(route('checkout.success', ['session_id' => Order::sole()->checkout_session_id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('order.status', 'processing')->where('cart.count', 2));
    }

    public function test_orders_record_the_configured_currency()
    {
        config(['shop.currency' => 'eur']);
        $this->fillBag();

        $this->post(route('checkout.store'));

        $this->assertSame('eur', Order::sole()->currency);
        $this->assertSame('eur', $this->gateway->createdFor[0]->currency);
    }

    public function test_only_one_concurrent_completion_wins()
    {
        $this->fillBag();
        $this->post(route('checkout.store'));

        // Two stale in-memory copies, as the success page and webhook would each load.
        $first = Order::sole();
        $second = Order::sole();
        $result = $this->gateway->retrieveCheckout($first->checkout_session_id);
        $complete = app(CompleteCheckout::class);

        $this->assertTrue($complete->handle($first, $result));
        $paidAt = $first->paid_at;

        $this->travel(1)->minute();
        $this->assertFalse($complete->handle($second, $result));

        $this->assertTrue($second->isPaid());
        $this->assertEquals($paidAt, Order::sole()->paid_at);
    }
}
