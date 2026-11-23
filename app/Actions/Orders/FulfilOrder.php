<?php

namespace App\Actions\Orders;

use App\Enums\FulfilmentStatus;
use App\Enums\OrderStatus;
use App\Mail\OrderShipped;
use App\Models\Order;
use Illuminate\Support\Facades\Mail;

/**
 * Moves a paid order through the warehouse: to pack → packed → shipped.
 * Each step is a conditional update, so two staff clicking at once can't
 * double-ship, and the shipping email goes out once.
 */
class FulfilOrder
{
    /** Orders that can be packed or shipped: paid, and not refunded in full. */
    private const SHIPPABLE = [OrderStatus::Paid, OrderStatus::PartiallyRefunded];

    public function pack(Order $order): bool
    {
        return $this->move($order, [FulfilmentStatus::Unfulfilled], [
            'fulfilment_status' => FulfilmentStatus::Packed,
            'packed_at' => now(),
        ]);
    }

    public function ship(Order $order, ?string $carrier, ?string $trackingNumber, ?string $trackingUrl): bool
    {
        $shipped = $this->move($order, [FulfilmentStatus::Unfulfilled, FulfilmentStatus::Packed], [
            'fulfilment_status' => FulfilmentStatus::Shipped,
            'packed_at' => $order->packed_at ?? now(),
            'shipped_at' => now(),
            'carrier' => $carrier ?: null,
            'tracking_number' => $trackingNumber ?: null,
            'tracking_url' => $trackingUrl ?: null,
        ]);

        if ($shipped && $order->email) {
            Mail::to($order->email)->queue(new OrderShipped($order));
        }

        return $shipped;
    }

    /**
     * @param  list<FulfilmentStatus>  $from
     * @param  array<string, mixed>  $attributes
     */
    private function move(Order $order, array $from, array $attributes): bool
    {
        $prepared = (new Order)->forceFill($attributes)->getAttributes();

        $updated = Order::query()
            ->whereKey($order->getKey())
            ->whereIn('status', self::SHIPPABLE)
            ->whereIn('fulfilment_status', $from)
            ->update([...array_intersect_key($prepared, $attributes), 'updated_at' => now()]);

        $order->refresh();

        return $updated === 1;
    }
}
