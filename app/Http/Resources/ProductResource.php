<?php

namespace App\Http\Resources;

use App\Models\Product;
use App\Models\StockLevel;
use App\Support\Media;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Product */
class ProductResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'name' => $this->name,
            'type' => $this->type,
            'description' => $this->description,
            'materials' => $this->materials,
            'price' => $this->price,
            'compareAtPrice' => $this->compare_at_price,
            'badge' => $this->badge?->value,
            'colours' => $this->colours,
            'sizes' => $this->sizes,
            'image' => Media::url($this->image),
            'swatch' => $this->swatch,
            // Listings: whether anything can be bought (see Product::scopeWithStockStatus).
            'inStock' => $this->when(isset($this->in_stock), fn () => (bool) $this->in_stock),
            // Product page: units per variant, capped at the per-line maximum so
            // exact stock counts aren't published.
            'stock' => $this->whenLoaded('stockLevels', fn () => $this->stockLevels->map(fn (StockLevel $level) => [
                'colour' => $level->colour,
                'size' => $level->size,
                'available' => min($level->available(), config('shop.max_quantity_per_line')),
            ])->values()),
            'href' => route('products.show', $this->resource),
            'category' => $this->whenLoaded('category', fn () => [
                'name' => $this->category->name,
                'slug' => $this->category->slug,
                'href' => route('shop.category', $this->category),
            ]),
        ];
    }
}
