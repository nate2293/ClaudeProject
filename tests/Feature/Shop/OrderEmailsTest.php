<?php

namespace Tests\Feature\Shop;

use App\Mail\OrderConfirmation;
use App\Models\Order;
use App\Models\Product;
use App\Payments\CheckoutStatus;
use App\Payments\PaymentGateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\Concerns\CreatesOrders;
use Tests\Fakes\FakePaymentGateway;
use Tests\TestCase;

class OrderEmailsTest extends TestCase
{
    use CreatesOrders, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app->instance(PaymentGateway::class, new FakePaymentGateway);
        Mail::fake();
    }

    public function test_the_confirmation_is_sent_once_however_often_payment_is_reported()
    {
        $order = $this->placeOrder();

        $this->settleOrder($order, CheckoutStatus::Paid);
        $this->settleOrder($order, CheckoutStatus::Paid); // e.g. webhook after the success page

        Mail::assertQueued(OrderConfirmation::class, 1);
        Mail::assertQueued(OrderConfirmation::class, fn ($mail) => $mail->hasTo('customer@example.com') && $mail->order->is($order));
    }

    public function test_delayed_payments_are_confirmed_when_they_settle_not_before()
    {
        $order = $this->placeOrder();

        $this->settleOrder($order, CheckoutStatus::Processing);
        Mail::assertNothingQueued();

        $this->settleOrder($order, CheckoutStatus::Paid);
        Mail::assertQueued(OrderConfirmation::class, 1);
    }

    public function test_the_confirmation_lists_items_totals_vat_and_address()
    {
        $order = $this->paidOrder(unitPrice: 3000, quantity: 2);

        $html = (new OrderConfirmation($order))->render();

        $this->assertStringContainsString($order->reference, $html);
        $this->assertStringContainsString($order->items->first()->product_name, $html);
        $this->assertStringContainsString('£64.95', $html);
        $this->assertStringContainsString('Includes VAT at 20%', $html);
        $this->assertStringContainsString('£10.83', $html); // 6495 × 20/120, rounded
        $this->assertStringContainsString('1 Test Street', $html);
    }

    public function test_checkout_records_the_vat_included_in_the_total()
    {
        $product = Product::factory()->create(['price' => 3000]);
        $this->post(route('cart.lines.store'), ['product_id' => $product->id, 'colour' => 'Black', 'size' => 'M', 'quantity' => 2]);

        $this->post(route('checkout.store'));

        $order = Order::sole();
        $this->assertSame(2000, $order->vat_rate);
        $this->assertSame(1083, $order->vat_amount);
    }

    public function test_no_vat_is_recorded_when_the_rate_is_zero()
    {
        config(['shop.vat.rate' => 0]);
        $product = Product::factory()->create(['price' => 3000]);
        $this->post(route('cart.lines.store'), ['product_id' => $product->id, 'colour' => 'Black', 'size' => 'M', 'quantity' => 1]);

        $this->post(route('checkout.store'));

        $this->assertSame(0, Order::sole()->vat_amount);
    }
}
