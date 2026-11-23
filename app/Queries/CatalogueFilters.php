<?php

namespace App\Queries;

use Illuminate\Http\Request;

/**
 * Browsing filters for the shop listing, read leniently from the query string.
 *
 * These come from shareable URLs (bookmarks, old links, hand-edited queries),
 * so unknown or malformed values fall back to defaults instead of failing
 * validation and bouncing the visitor elsewhere.
 */
final readonly class CatalogueFilters
{
    public const SORTS = ['featured', 'newest', 'price-asc', 'price-desc'];

    private const MAX_SEARCH_LENGTH = 100;

    private const MAX_TYPE_LENGTH = 50;

    public function __construct(
        public ?string $q = null,
        public ?string $type = null,
        public string $sort = 'featured',
        public bool $sale = false,
    ) {}

    public static function fromRequest(Request $request): self
    {
        $sort = self::string($request, 'sort');

        return new self(
            q: self::string($request, 'q', self::MAX_SEARCH_LENGTH),
            type: self::string($request, 'type', self::MAX_TYPE_LENGTH),
            sort: in_array($sort, self::SORTS, true) ? $sort : 'featured',
            sale: filter_var($request->query('sale'), FILTER_VALIDATE_BOOLEAN),
        );
    }

    /** @return array{q: ?string, type: ?string, sort: string, sale: bool} */
    public function toArray(): array
    {
        return ['q' => $this->q, 'type' => $this->type, 'sort' => $this->sort, 'sale' => $this->sale];
    }

    /** A trimmed, length-limited string parameter, or null for missing, empty or non-string values. */
    private static function string(Request $request, string $key, int $maxLength = 50): ?string
    {
        $value = $request->query($key);

        if (! is_string($value)) {
            return null;
        }

        $value = mb_substr(trim($value), 0, $maxLength);

        return $value === '' ? null : $value;
    }
}
