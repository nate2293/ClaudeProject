<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    private const RELATED_COUNT = 4;

    /** How long a product's related-product picks are cached. */
    private const RELATED_CACHE_SECONDS = 3600;

    public function show(Product $product): Response
    {
        abort_unless($product->is_active, 404);

        $product->load('category', 'stockLevels');

        return Inertia::render('products/show', [
            'product' => new ProductResource($product),
            'related' => ProductResource::collection($this->related($product)),
        ]);
    }

    /**
     * Other products from the same category: the same type first (more jumpers
     * for a jumper), then featured, then newest. The IDs are cached per product,
     * but the products are loaded fresh, so price changes show at once and a
     * deactivated product drops out straight away.
     *
     * @return Collection<int, Product>
     */
    private function related(Product $product): Collection
    {
        $ids = Cache::remember("products.{$product->id}.related", self::RELATED_CACHE_SECONDS, fn () => Product::active()
            ->whereBelongsTo($product->category)
            ->whereKeyNot($product->id)
            ->orderByRaw('case when type = ? then 0 else 1 end', [$product->type])
            ->orderByDesc('is_featured')
            ->orderByDesc('id')
            ->limit(self::RELATED_COUNT)
            ->pluck('id')
            ->all());

        return Product::active()
            ->whereKey($ids)
            ->with('category', 'stockLevels')
            ->withStockStatus()
            ->get()
            ->sortBy(fn (Product $related) => array_search($related->id, $ids, true))
            ->values();
    }
}
