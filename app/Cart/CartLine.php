<?php

namespace App\Cart;

use App\Models\Product;

/**
 * A single line in the shopping bag, hydrated with its current product data.
 */
final readonly class CartLine
{
    public function __construct(
        public string $key,
        public Product $product,
        public string $colour,
        public string $size,
        public int $quantity,
        /** The variant's stock level; null if the variant isn't stocked. */
        public ?int $stockLevelId = null,
        /** Units this shopper can have: free stock plus what their open checkout holds. */
        public int $available = 0,
    ) {}

    /** Whether the line can be bought at its current quantity. */
    public function isAvailable(): bool
    {
        return $this->quantity <= $this->available;
    }

    public function unitPrice(): int
    {
        return $this->product->price;
    }

    public function total(): int
    {
        return $this->unitPrice() * $this->quantity;
    }

    public function colourHex(): ?string
    {
        return collect($this->product->colours)->firstWhere('name', $this->colour)['hex'] ?? null;
    }
}
