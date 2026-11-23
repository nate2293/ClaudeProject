<?php

namespace Tests\Feature\Admin;

use App\Actions\Orders\RecordRefund;
use App\Enums\FulfilmentStatus;
use App\Enums\OrderStatus;
use App\Filament\Resources\Orders\Pages\ListOrders;
use App\Filament\Resources\Orders\Pages\ViewOrder;
use App\Inventory\Inventory;
use App\Mail\OrderRefunded;
use App\Mail\OrderShipped;
use App\Models\StockLevel;
use App\Models\User;
use App\Payments\PaymentGateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\Concerns\CreatesOrders;
use Tests\Fakes\FakePaymentGateway;
use Tests\TestCase;

class OrderAdminTest extends TestCase
{
    use CreatesOrders, RefreshDatabase;

    private FakePaymentGateway $gateway;

    protected function setUp(): void
    {
        parent::setUp();

        $this->gateway = new FakePaymentGateway;
        $this->app->instance(PaymentGateway::class, $this->gateway);
        $this->actingAs(User::factory()->create()->forceFill(['is_admin' => true]));
        Mail::fake();
    }

    private function orderPage($order)
    {
        return Livewire::test(ViewOrder::class, ['record' => $order->getRouteKey()]);
    }

    public function test_the_default_tab_lists_paid_orders_waiting_to_ship()
    {
        $toShip = $this->paidOrder();
        $abandoned = $this->placeOrder();

        Livewire::test(ListOrders::class)
            ->assertCanSeeTableRecords([$toShip])
            ->assertCanNotSeeTableRecords([$abandoned]);
    }

    public function test_an_order_is_packed_then_shipped_and_the_customer_emailed_once()
    {
        $order = $this->paidOrder();

        $this->orderPage($order)->callAction('pack');
        $this->assertSame(FulfilmentStatus::Packed, $order->refresh()->fulfilment_status);

        $this->orderPage($order)->callAction('ship', data: [
            'carrier' => 'Royal Mail', 'tracking_number' => 'RM123456789GB', 'tracking_url' => 'https://www.royalmail.com/track-your-item#/tracking-results/RM123456789GB',
        ])->assertHasNoActionErrors();

        $order->refresh();
        $this->assertSame(FulfilmentStatus::Shipped, $order->fulfilment_status);
        $this->assertSame('RM123456789GB', $order->tracking_number);
        $this->assertNotNull($order->shipped_at);
        Mail::assertQueued(OrderShipped::class, 1);

        $this->orderPage($order)->assertActionHidden('ship');
        $this->assertStringContainsString('Track your parcel', (new OrderShipped($order))->render());
    }

    public function test_tracking_links_must_be_web_addresses()
    {
        $order = $this->paidOrder();

        $this->orderPage($order)->callAction('ship', data: ['tracking_url' => 'data://text/html,<script>alert(1)</script>'])
            ->assertHasActionErrors(['tracking_url']);

        $this->assertSame(FulfilmentStatus::Unfulfilled, $order->refresh()->fulfilment_status);
    }

    public function test_unpaid_orders_cannot_be_shipped()
    {
        $order = $this->placeOrder();

        $this->orderPage($order)->assertActionHidden('pack')->assertActionHidden('ship')->assertActionHidden('refund');
    }

    public function test_a_partial_refund_goes_through_stripe_restocks_and_emails_once()
    {
        $order = $this->paidOrder(unitPrice: 3000, quantity: 2); // £64.95
        $item = $order->items->sole();
        $onHandBefore = StockLevel::find($item->stock_level_id)->on_hand;

        $this->orderPage($order)->callAction('refund', data: [
            'amount' => '30.00',
            'restock' => [$item->id => 1],
        ])->assertHasNoActionErrors();

        $this->assertSame([['reference' => 'pi_test_123', 'amount' => 3000, 'key' => "refund-{$order->reference}-0-3000"]], $this->gateway->refunds);
        $order->refresh();
        $this->assertSame(OrderStatus::PartiallyRefunded, $order->status);
        $this->assertSame(3000, $order->amount_refunded);
        $this->assertSame($onHandBefore + 1, StockLevel::find($item->stock_level_id)->on_hand);
        $this->assertSame(1, $item->refresh()->restocked_quantity);
        Mail::assertQueued(OrderRefunded::class, fn ($mail) => $mail->amount === 3000);

        // Stripe's webhook for the same refund arrives afterwards: nothing more happens.
        app(RecordRefund::class)->handle($order, 3000);
        Mail::assertQueued(OrderRefunded::class, 1);
    }

    public function test_more_than_the_refundable_amount_is_refused()
    {
        $order = $this->paidOrder();

        $this->orderPage($order)->callAction('refund', data: ['amount' => '100.00'])->assertHasActionErrors(['amount']);

        $this->assertSame([], $this->gateway->refunds);
    }

    public function test_a_declined_refund_changes_nothing()
    {
        $order = $this->paidOrder();
        $this->gateway->failOnRefund = true;

        $this->orderPage($order)->callAction('refund', data: ['amount' => '10.00']);

        $this->assertSame(0, $order->refresh()->amount_refunded);
        Mail::assertNotQueued(OrderRefunded::class);
    }

    public function test_returned_units_can_be_restocked_once()
    {
        $order = $this->paidOrder(quantity: 2);
        $item = $order->items->sole();
        $level = StockLevel::find($item->stock_level_id);
        $before = $level->on_hand;

        $this->orderPage($order)->callAction('restock', data: ['restock' => [$item->id => 2]]);
        $this->assertSame($before + 2, $level->refresh()->on_hand);

        $this->orderPage($order)->assertActionHidden('restock');
        $this->assertSame(0, app(Inventory::class)->restock($item->refresh(), 1));
    }

    public function test_a_review_flag_can_be_cleared()
    {
        $order = $this->paidOrder();
        $order->forceFill(['review_reason' => 'Amount paid differs.'])->save();

        $this->orderPage($order)->callAction('resolveReview');

        $this->assertNull($order->refresh()->review_reason);
    }
}
