<?php

namespace Tests\Concerns;

use App\Actions\Checkout\CompleteCheckout;
use App\Inventory\Inventory;
use App\Models\Order;
use App\Models\Product;
use App\Models\StockLevel;
use App\Payments\CheckoutStatus;
use App\Payments\PaymentGateway;
use Tests\Fakes\FakePaymentGateway;

/**
 * Builds orders the way checkout does (items linked to stock, units reserved),
 * then settles them through CompleteCheckout, so tests exercise real flows.
 */
trait CreatesOrders
{
    protected function placeOrder(int $unitPrice = 3000, int $quantity = 2, int $stock = 10): Order
    {
        $product = Product::factory()->stocked($stock)->create(['price' => $unitPrice]);
        $level = StockLevel::where(['product_id' => $product->id, 'colour' => 'Black', 'size' => 'M'])->sole();
        $subtotal = $unitPrice * $quantity;

        $order = Order::create([
            'currency' => 'gbp', 'subtotal' => $subtotal, 'shipping' => 495, 'total' => $subtotal + 495,
            'vat_rate' => 2000, 'vat_amount' => (int) round(($subtotal + 495) * 2000 / 12000),
            'checkout_session_id' => 'cs_test_'.uniqid(),
        ]);
        $order->items()->create([
            'product_id' => $product->id, 'stock_level_id' => $level->id, 'product_name' => $product->name,
            'colour' => 'Black', 'size' => 'M', 'unit_price' => $unitPrice, 'quantity' => $quantity, 'line_total' => $subtotal,
        ]);
        app(Inventory::class)->reserve($order);

        return $order->refresh();
    }

    protected function paidOrder(int $unitPrice = 3000, int $quantity = 2, int $stock = 10): Order
    {
        $order = $this->placeOrder($unitPrice, $quantity, $stock);
        $this->settleOrder($order, CheckoutStatus::Paid);

        return $order->refresh();
    }

    protected function settleOrder(Order $order, CheckoutStatus $status): bool
    {
        $gateway = app(PaymentGateway::class);

        if ($gateway instanceof FakePaymentGateway) {
            $gateway->status = $status;
        }

        return app(CompleteCheckout::class)->handle($order, $gateway->retrieveCheckout($order->checkout_session_id));
    }
}
