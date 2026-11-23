<?php

namespace App\Models;

use Database\Factories\CategoryFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;

class Category extends Model
{
    /** @use HasFactory<CategoryFactory> */
    use HasFactory;

    /** Cache key for the shop navigation (shared with every page). */
    public const NAVIGATION_CACHE_KEY = 'shop.navigation';

    protected $fillable = ['name', 'slug', 'description', 'image', 'swatch', 'position'];

    protected static function booted(): void
    {
        // Any change to a category refreshes the navigation on the next request.
        static::saved(function (self $category) {
            Cache::forget(self::NAVIGATION_CACHE_KEY);

            // Product search documents include the category name.
            if ($category->wasChanged('name')) {
                $category->products()->searchable();
            }
        });
        static::deleted(fn () => Cache::forget(self::NAVIGATION_CACHE_KEY));
    }

    /**
     * Name and slug of every category in menu order, cached because it's needed
     * on every page view. URLs are built per request, since they depend on the host.
     *
     * @return list<array{name: string, slug: string}>
     */
    public static function navigation(): array
    {
        return Cache::remember(self::NAVIGATION_CACHE_KEY, now()->addDay(), fn () => static::ordered()
            ->get(['name', 'slug'])
            ->map(fn (self $category) => $category->only('name', 'slug'))
            ->all());
    }

    /** @return HasMany<Product, $this> */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /** @param Builder<self> $query */
    public function scopeOrdered(Builder $query): void
    {
        $query->orderBy('position')->orderBy('name');
    }
}
