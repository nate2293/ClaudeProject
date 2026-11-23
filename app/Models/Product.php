<?php

namespace App\Models;

use App\Enums\ProductBadge;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Scout\Searchable;

/**
 * @property list<array{name: string, hex: string}> $colours
 * @property list<string> $sizes
 */
class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory;

    use Searchable;

    protected $fillable = [
        'category_id', 'name', 'slug', 'type', 'description', 'materials', 'price', 'compare_at_price',
        'badge', 'colours', 'sizes', 'image', 'swatch', 'is_featured', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'integer',
            'compare_at_price' => 'integer',
            'badge' => ProductBadge::class,
            'colours' => 'array',
            'sizes' => 'array',
            'is_featured' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function searchableAs(): string
    {
        return 'products';
    }

    /** Only products on sale are indexed; deactivating one removes it from the index. */
    public function shouldBeSearchable(): bool
    {
        return $this->is_active;
    }

    /**
     * The document sent to the search engine. Attribute order matters to
     * Meilisearch's ranking: see `index-settings` in config/scout.php.
     *
     * @return array<string, mixed>
     */
    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'type' => $this->type,
            'colours' => array_column($this->colours ?? [], 'name'),
            'category' => $this->category?->name,
            'materials' => $this->materials,
            'description' => $this->description,
        ];
    }

    /** @param Builder<self> $query */
    protected function makeAllSearchableUsing(Builder $query): Builder
    {
        return $query->with('category');
    }

    /** @return BelongsTo<Category, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /** @return HasMany<StockLevel, $this> */
    public function stockLevels(): HasMany
    {
        return $this->hasMany(StockLevel::class);
    }

    /**
     * Adds an `in_stock` flag: whether any variant has units available. Used by
     * product listings to mark sold-out products.
     *
     * @param  Builder<self>  $query
     */
    public function scopeWithStockStatus(Builder $query): void
    {
        $query->withExists(['stockLevels as in_stock' => fn (Builder $q) => $q->whereColumn('on_hand', '>', 'reserved')]);
    }

    /** @param Builder<self> $query */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /** @param Builder<self> $query */
    public function scopeOnSale(Builder $query): void
    {
        $query->whereNotNull('compare_at_price')->whereColumn('compare_at_price', '>', 'price');
    }

    public function hasColour(string $name): bool
    {
        return collect($this->colours)->contains('name', $name);
    }

    public function hasSize(string $size): bool
    {
        return in_array($size, $this->sizes, true);
    }
}
