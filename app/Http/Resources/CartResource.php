<?php

namespace App\Http\Resources;

use App\Cart\Cart;
use App\Cart\CartLine;
use App\Support\Media;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @property Cart $resource */
class CartResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $cart = $this->resource;

        return [
            'lines' => $cart->lines()->values()->map(fn (CartLine $line) => [
                'key' => $line->key,
                'colour' => $line->colour,
                'colourHex' => $line->colourHex(),
                'size' => $line->size,
                'quantity' => $line->quantity,
                'available' => min($line->available, $cart->maxQuantity()),
                'unitPrice' => $line->unitPrice(),
                'total' => $line->total(),
                'product' => [
                    'name' => $line->product->name,
                    'type' => $line->product->type,
                    'swatch' => $line->product->swatch,
                    'image' => Media::url($line->product->image),
                    'href' => route('products.show', $line->product),
                ],
            ]),
            'count' => $cart->count(),
            'hasStockIssues' => $cart->hasStockIssues(),
            'subtotal' => $cart->subtotal(),
            'shipping' => $cart->shipping(),
            'total' => $cart->total(),
        ];
    }
}
