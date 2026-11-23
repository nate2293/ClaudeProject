<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Stock for one variant of a product. Changed only through App\Inventory\Inventory,
 * which uses conditional updates so concurrent checkouts can't oversell.
 */
class StockLevel extends Model
{
    protected $fillable = ['product_id', 'colour', 'size', 'on_hand'];

    protected function casts(): array
    {
        return ['on_hand' => 'integer', 'reserved' => 'integer'];
    }

    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /** Units that can still be sold. */
    public function available(): int
    {
        return max(0, $this->on_hand - $this->reserved);
    }

    public static function keyFor(int $productId, string $colour, string $size): string
    {
        return "{$productId}|{$colour}|{$size}";
    }

    public function variantKey(): string
    {
        return self::keyFor($this->product_id, $this->colour, $this->size);
    }
}
