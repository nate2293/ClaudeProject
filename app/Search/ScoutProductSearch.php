<?php

namespace App\Search;

use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Throwable;

/**
 * Search through Laravel Scout (Meilisearch in production): typo tolerance,
 * relevance ranking, and matches on colours, materials and category too.
 *
 * The engine only returns matching IDs; the database then applies the shop's
 * filters, sorting and pagination as usual. If the engine can't be reached the
 * error is reported and the database search is used, so search never goes down.
 */
class ScoutProductSearch implements ProductSearch
{
    /** @var array<string, list<int>> Results per term, as a page queries twice (results and types). */
    private array $results = [];

    public function __construct(
        private readonly DatabaseProductSearch $fallback,
        private readonly int $maxResults,
    ) {}

    public function constrain(Builder $query, string $term): ?array
    {
        try {
            $ids = $this->results[$term] ??= Product::search($term)
                ->take($this->maxResults)
                ->keys()
                ->map(fn ($id) => (int) $id)
                ->all();
        } catch (Throwable $e) {
            report($e);

            return $this->fallback->constrain($query, $term);
        }

        $query->whereKey($ids);

        return $ids;
    }
}
