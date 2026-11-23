<?php

namespace App\Search;

use Illuminate\Database\Eloquent\Builder;

/**
 * Substring search in the database. Needs no extra service and works on SQLite,
 * MySQL and PostgreSQL, but can't use an index or rank results: fine for a few
 * thousand products, beyond that switch SCOUT_DRIVER to a search engine.
 */
class DatabaseProductSearch implements ProductSearch
{
    /** Columns matched by the search box. */
    private const SEARCHABLE = ['name', 'type', 'description'];

    /**
     * Escape character for LIKE patterns. A non-backslash character is used because
     * backslash escaping behaves differently across SQLite, MySQL and PostgreSQL.
     */
    private const LIKE_ESCAPE = '!';

    /**
     * Match the term literally and case-insensitively: `%` and `_` typed by the
     * shopper are escaped so they don't act as wildcards (e.g. "100%" shouldn't
     * match everything), and both sides are lowercased because LIKE is
     * case-insensitive on SQLite and MySQL but case-sensitive on PostgreSQL.
     */
    public function constrain(Builder $query, string $term): ?array
    {
        $e = self::LIKE_ESCAPE;
        $pattern = '%'.str_replace([$e, '%', '_'], [$e.$e, $e.'%', $e.'_'], mb_strtolower($term)).'%';
        $grammar = $query->getQuery()->getGrammar();

        $query->where(function (Builder $q) use ($pattern, $grammar, $e) {
            foreach (self::SEARCHABLE as $column) {
                $q->orWhereRaw('lower('.$grammar->wrap($column).") like ? escape '{$e}'", [$pattern]);
            }
        });

        return null;
    }
}
