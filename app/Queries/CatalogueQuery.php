<?php

namespace App\Queries;

use App\Models\Category;
use App\Models\Product;
use App\Search\ProductSearch;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Builds the filtered, sorted product listing used by the shop pages.
 */
class CatalogueQuery
{
    private readonly ProductSearch $search;

    /** @var list<int>|null Search results in relevance order, when the engine ranks them. */
    private ?array $ranked = null;

    public function __construct(
        private readonly ?Category $category,
        private readonly CatalogueFilters $filters,
        ?ProductSearch $search = null,
    ) {
        $this->search = $search ?? app(ProductSearch::class);
    }

    /** @return LengthAwarePaginator<int, Product> */
    public function paginate(int $perPage): LengthAwarePaginator
    {
        $query = $this->scoped()->with('category')->withStockStatus();

        if ($this->filters->type) {
            $query->where('type', $this->filters->type);
        }

        $this->applySort($query, $this->filters->sort);

        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * Product types available in the current scope (category, search and sale),
     * ignoring the type filter itself so every option stays selectable.
     *
     * @return Collection<int, string>
     */
    public function availableTypes(): Collection
    {
        return $this->scoped()->distinct()->orderBy('type')->pluck('type');
    }

    /** @return Builder<Product> */
    private function scoped(): Builder
    {
        return Product::query()
            ->active()
            ->when($this->category, fn (Builder $q, Category $c) => $q->whereBelongsTo($c))
            ->when($this->filters->sale, fn (Builder $q) => $q->onSale())
            ->when($this->filters->q, function (Builder $q, string $term) {
                $this->ranked = $this->search->constrain($q, $term);
            });
    }

    /** @param Builder<Product> $query */
    private function applySort(Builder $query, string $sort): void
    {
        // The default order becomes "best match" when the search engine ranks results.
        // IDs and positions are integers we cast, so they're inlined: bound values
        // would leave the CASE result untyped, and PostgreSQL would sort it as text.
        if ($sort === 'featured' && $this->ranked) {
            $cases = collect($this->ranked)->map(fn (int $id, int $position) => 'when '.(int) $id.' then '.(int) $position)->implode(' ');
            $query->orderByRaw('case '.$query->getQuery()->getGrammar()->wrap('id')." {$cases} end");

            return;
        }

        match ($sort) {
            'newest' => $query->latest('id'),
            'price-asc' => $query->orderBy('price')->orderBy('id'),
            'price-desc' => $query->orderByDesc('price')->orderBy('id'),
            default => $query->orderByDesc('is_featured')->orderBy('id'),
        };
    }
}
