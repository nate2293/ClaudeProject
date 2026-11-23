<?php

namespace App\Inventory;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\StockLevel;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Stock reservations for orders.
 *
 *   checkout starts ──▶ reserve   (reserved += qty, only if on_hand - reserved >= qty)
 *   order paid      ──▶ commit    (on_hand -= qty, reserved -= qty)
 *   cancelled/failed──▶ release   (reserved -= qty)
 *
 * Every change is one conditional UPDATE, so two shoppers racing for the last
 * unit can't both reserve it. Commit and release are called only by the order
 * status change that settles the order (see CompleteCheckout), which happens
 * exactly once, so stock is never counted twice.
 */
class Inventory
{
    /**
     * Hold stock for every line of the order, or for none of them.
     *
     * @throws InsufficientStock
     */
    public function reserve(Order $order): void
    {
        DB::transaction(function () use ($order) {
            $shortages = [];

            // Every line, not just tracked ones: a variant with no stock level can't be sold.
            foreach ($order->items()->orderBy('stock_level_id')->get() as $item) {
                $held = $item->stock_level_id && StockLevel::query()
                    ->whereKey($item->stock_level_id)
                    ->whereRaw('on_hand - reserved >= ?', [$item->quantity])
                    ->increment('reserved', $item->quantity);

                if (! $held) {
                    $shortages[] = [
                        'name' => $item->product_name,
                        'colour' => $item->colour,
                        'size' => $item->size,
                        'requested' => $item->quantity,
                        'available' => $item->stock_level_id ? (StockLevel::find($item->stock_level_id)?->available() ?? 0) : 0,
                    ];
                }
            }

            if ($shortages) {
                throw new InsufficientStock($shortages); // Rolls back the lines already held.
            }
        });
    }

    /**
     * Turn the order's reservation into a sale.
     *
     * @return list<OrderItem> Lines whose stock went below zero (sold more than on hand).
     */
    public function commit(Order $order): array
    {
        $oversold = [];

        foreach ($this->trackedItems($order) as $item) {
            StockLevel::query()->whereKey($item->stock_level_id)->toBase()
                ->decrementEach(['on_hand' => $item->quantity, 'reserved' => $item->quantity], ['updated_at' => now()]);

            if (StockLevel::whereKey($item->stock_level_id)->where('on_hand', '<', 0)->exists()) {
                $oversold[] = $item;
            }
        }

        return $oversold;
    }

    /** Return the order's reserved units to sale. */
    public function release(Order $order): void
    {
        foreach ($this->trackedItems($order) as $item) {
            StockLevel::query()
                ->whereKey($item->stock_level_id)
                ->where('reserved', '>=', $item->quantity)
                ->decrement('reserved', $item->quantity);
        }
    }

    /**
     * Put units of a sold line back into stock (e.g. a return). Never more than
     * were sold on that line, however many times it's called.
     *
     * @return int Units actually restocked.
     */
    public function restock(OrderItem $item, int $units): int
    {
        if (! $item->stock_level_id || $units < 1) {
            return 0;
        }

        return DB::transaction(function () use ($item, $units) {
            $claimed = OrderItem::query()
                ->whereKey($item->getKey())
                ->whereRaw('restocked_quantity + ? <= quantity', [$units])
                ->increment('restocked_quantity', $units);

            if (! $claimed) {
                return 0;
            }

            StockLevel::query()->whereKey($item->stock_level_id)->increment('on_hand', $units);
            $item->refresh();

            return $units;
        });
    }

    /**
     * Lines that reserved stock, in a fixed order so concurrent transactions lock
     * rows in the same sequence (avoids deadlocks).
     *
     * @return Collection<int, OrderItem>
     */
    private function trackedItems(Order $order): Collection
    {
        return $order->items()->whereNotNull('stock_level_id')->orderBy('stock_level_id')->get();
    }
}
