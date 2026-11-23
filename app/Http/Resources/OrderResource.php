<?php

namespace App\Http\Resources;

use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Order */
class OrderResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'reference' => $this->reference,
            'status' => $this->status->value,
            'statusLabel' => $this->status->label(),
            'currency' => $this->currency,
            'subtotal' => $this->subtotal,
            'shipping' => $this->shipping,
            'total' => $this->total,
            'placedAt' => $this->created_at->toIso8601String(),
            'items' => $this->items->map(fn (OrderItem $item) => [
                'id' => $item->id,
                'name' => $item->product_name,
                'colour' => $item->colour,
                'size' => $item->size,
                'quantity' => $item->quantity,
                'unitPrice' => $item->unit_price,
                'total' => $item->line_total,
            ]),
        ];
    }
}
