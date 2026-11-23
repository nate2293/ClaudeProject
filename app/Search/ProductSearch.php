<?php

namespace App\Search;

use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;

/**
 * Full-text matching for the catalogue. Implementations narrow a product query
 * to a search term; filters, sorting and pagination stay in the database.
 *
 * Which one runs depends on SCOUT_DRIVER: "null" (the default) uses the
 * database, anything else (e.g. "meilisearch") uses Laravel Scout.
 */
interface ProductSearch
{
    /**
     * Restrict the query to products matching the term.
     *
     * @param  Builder<Product>  $query
     * @return list<int>|null Matching IDs in relevance order, or null if the engine doesn't rank.
     */
    public function constrain(Builder $query, string $term): ?array;
}
