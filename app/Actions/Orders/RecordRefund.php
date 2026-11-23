<?php

namespace App\Actions\Orders;

use App\Enums\OrderStatus;
use App\Mail\OrderRefunded;
use App\Models\Order;
use Illuminate\Support\Facades\Mail;

/**
 * Records the payment provider's running refund total on an order and emails the
 * customer about the newly refunded amount.
 *
 * Called by the refund webhook and straight after a refund made from the admin
 * area, in either order. Totals are cumulative and only ever move forward
 * (compare-and-set), so each refunded penny is recorded and emailed once.
 */
class RecordRefund
{
    /** @return int The amount newly recorded by this call (0 if already known). */
    public function handle(Order $order, int $totalRefunded): int
    {
        for ($attempt = 0; $attempt < 3; $attempt++) {
            $order->refresh();
            $previous = $order->amount_refunded;

            if ($totalRefunded <= $previous || ! $order->wasPaidFor()) {
                return 0;
            }

            $paid = $order->amount_paid ?? $order->total;
            $full = $totalRefunded >= $paid;

            $updated = Order::query()
                ->whereKey($order->getKey())
                ->where('amount_refunded', $previous) // Compare-and-set: lost a race? Re-read and retry.
                ->whereIn('status', OrderStatus::paidFor())
                ->update([
                    'amount_refunded' => $totalRefunded,
                    'status' => $full ? OrderStatus::Refunded : OrderStatus::PartiallyRefunded,
                    'refunded_at' => $full ? now() : $order->refunded_at,
                    'updated_at' => now(),
                ]);

            if ($updated === 1) {
                $order->refresh();
                $delta = $totalRefunded - $previous;

                if ($order->email) {
                    Mail::to($order->email)->queue(new OrderRefunded($order, $delta));
                }

                return $delta;
            }
        }

        return 0;
    }
}
