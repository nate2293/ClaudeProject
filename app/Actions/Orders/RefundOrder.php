<?php

namespace App\Actions\Orders;

use App\Inventory\Inventory;
use App\Models\Order;
use App\Payments\PaymentException;
use App\Payments\PaymentGateway;
use InvalidArgumentException;

/**
 * Refunds an order from the admin area: refunds the money through the payment
 * provider, records it (emailing the customer), and optionally puts returned
 * units back into stock.
 */
class RefundOrder
{
    public function __construct(
        private readonly PaymentGateway $gateway,
        private readonly RecordRefund $recordRefund,
        private readonly Inventory $inventory,
    ) {}

    /**
     * @param  int  $amount  Minor units to refund now.
     * @param  array<int, int>  $restock  Order item ID => units to put back into stock.
     *
     * @throws InvalidArgumentException When the amount isn't refundable.
     * @throws PaymentException
     */
    public function handle(Order $order, int $amount, array $restock = []): void
    {
        if (! $order->wasPaidFor() || ! $order->payment_reference) {
            throw new InvalidArgumentException('Only paid orders can be refunded.');
        }

        if ($amount < 1 || $amount > $order->refundableAmount()) {
            throw new InvalidArgumentException('The refund must be between 1p and the amount not yet refunded.');
        }

        // Keyed on what's been refunded so far: a double-click or retry sends the
        // same key, so the provider refunds once.
        $key = "refund-{$order->reference}-{$order->amount_refunded}-{$amount}";
        $total = $this->gateway->refund($order->payment_reference, $amount, $key);

        $this->recordRefund->handle($order, $total);

        foreach ($order->items as $item) {
            if (($restock[$item->id] ?? 0) > 0) {
                $this->inventory->restock($item, $restock[$item->id]);
            }
        }
    }
}
