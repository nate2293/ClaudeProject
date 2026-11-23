<?php

namespace App\Cart;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Product;
use App\Models\StockLevel;
use Illuminate\Contracts\Session\Session;
use Illuminate\Support\Collection;

/**
 * Session-backed shopping bag.
 *
 * Only product IDs, options and quantities are stored in the session. Prices are
 * always read from the database when the cart is hydrated, so they can't be
 * tampered with client-side and always reflect the current catalogue. Stock is
 * read live too: units held by this shopper's own open checkout count as theirs.
 */
class Cart
{
    private const SESSION_KEY = 'cart.lines';

    /** This browser's latest unpaid order, whose checkout may still be open. */
    private const PENDING_ORDER_KEY = 'checkout.pending_order';

    /** @var Collection<string, CartLine>|null */
    private ?Collection $hydrated = null;

    public function __construct(private readonly Session $session) {}

    public function add(Product $product, string $colour, string $size, int $quantity = 1): void
    {
        $key = $this->keyFor($product->id, $colour, $size);
        $stored = $this->stored();
        $existing = $stored[$key]['quantity'] ?? 0;

        $stored[$key] = [
            'product_id' => $product->id,
            'colour' => $colour,
            'size' => $size,
            'quantity' => min($existing + $quantity, $this->maxQuantity()),
        ];

        $this->persist($stored);
    }

    public function update(string $key, int $quantity): void
    {
        $stored = $this->stored();

        if (! isset($stored[$key])) {
            return;
        }

        $stored[$key]['quantity'] = max(1, min($quantity, $this->maxQuantity()));
        $this->persist($stored);
    }

    public function remove(string $key): void
    {
        $stored = $this->stored();
        unset($stored[$key]);
        $this->persist($stored);
    }

    public function clear(): void
    {
        $this->persist([]);
    }

    public function has(string $key): bool
    {
        return isset($this->stored()[$key]);
    }

    public function line(string $key): ?CartLine
    {
        return $this->lines()->get($key);
    }

    /** Units of this variant already in the bag. */
    public function quantityOf(Product $product, string $colour, string $size): int
    {
        return $this->stored()[$this->keyFor($product->id, $colour, $size)]['quantity'] ?? 0;
    }

    /** Units of this variant the shopper can have in total. */
    public function availableFor(Product $product, string $colour, string $size): int
    {
        $level = StockLevel::where(['product_id' => $product->id, 'colour' => $colour, 'size' => $size])->first();

        return $level ? $level->available() + ($this->ownReservations()[$level->id] ?? 0) : 0;
    }

    /** Whether any line wants more than is available (checkout would be refused). */
    public function hasStockIssues(): bool
    {
        return $this->lines()->contains(fn (CartLine $line) => ! $line->isAvailable());
    }

    public function pendingOrderReference(): ?string
    {
        return $this->session->get(self::PENDING_ORDER_KEY);
    }

    public function rememberPendingOrder(Order $order): void
    {
        $this->session->put(self::PENDING_ORDER_KEY, $order->reference);
    }

    public function forgetPendingOrder(): void
    {
        $this->session->forget(self::PENDING_ORDER_KEY);
    }

    /**
     * Cart lines hydrated with live product data. Lines whose product has been
     * removed or deactivated are dropped.
     *
     * @return Collection<string, CartLine>
     */
    public function lines(): Collection
    {
        if ($this->hydrated !== null) {
            return $this->hydrated;
        }

        $stored = collect($this->stored());

        if ($stored->isEmpty()) {
            return $this->hydrated = collect();
        }

        $products = Product::query()
            ->active()
            ->whereIn('id', $stored->pluck('product_id')->unique())
            ->get()
            ->keyBy('id');

        $available = $stored->filter(fn (array $line) => $products->has($line['product_id']));

        // Forget lines for products that are gone, so the session, the header
        // count and the bag page always agree.
        if ($available->count() !== $stored->count()) {
            $this->persist($available->all());
        }

        $levels = StockLevel::query()
            ->whereIn('product_id', $available->pluck('product_id')->unique())
            ->get()
            ->keyBy(fn (StockLevel $level) => $level->variantKey());
        $own = $this->ownReservations();

        return $this->hydrated = $available
            ->map(function (array $line, string $key) use ($products, $levels, $own) {
                $level = $levels->get(StockLevel::keyFor($line['product_id'], $line['colour'], $line['size']));

                return new CartLine(
                    key: $key,
                    product: $products[$line['product_id']],
                    colour: $line['colour'],
                    size: $line['size'],
                    quantity: $line['quantity'],
                    stockLevelId: $level?->id,
                    available: $level ? $level->available() + ($own[$level->id] ?? 0) : 0,
                );
            });
    }

    /** Total number of units of products that are still for sale. */
    public function count(): int
    {
        return $this->lines()->sum(fn (CartLine $line) => $line->quantity);
    }

    public function isEmpty(): bool
    {
        return $this->lines()->isEmpty();
    }

    public function subtotal(): int
    {
        return $this->lines()->sum(fn (CartLine $line) => $line->total());
    }

    public function shipping(): int
    {
        $subtotal = $this->subtotal();

        if ($subtotal === 0 || $subtotal >= config('shop.shipping.free_threshold')) {
            return 0;
        }

        return config('shop.shipping.standard_rate');
    }

    public function total(): int
    {
        return $this->subtotal() + $this->shipping();
    }

    public function maxQuantity(): int
    {
        return config('shop.max_quantity_per_line');
    }

    /**
     * Units held for this shopper by their own open checkout, keyed by stock level.
     * Starting checkout again releases them first, so they count as available here.
     *
     * @return array<int, int>
     */
    private function ownReservations(): array
    {
        $reference = $this->pendingOrderReference();

        if (! $reference) {
            return [];
        }

        return Order::query()
            ->where('reference', $reference)
            ->where('status', OrderStatus::Pending)
            ->first()
            ?->items()
            ->whereNotNull('stock_level_id')
            ->pluck('quantity', 'stock_level_id')
            ->all() ?? [];
    }

    private function keyFor(int $productId, string $colour, string $size): string
    {
        return substr(hash('sha256', "{$productId}|{$colour}|{$size}"), 0, 16);
    }

    /** @return array<string, array{product_id: int, colour: string, size: string, quantity: int}> */
    private function stored(): array
    {
        return $this->session->get(self::SESSION_KEY, []);
    }

    /** @param array<string, array{product_id: int, colour: string, size: string, quantity: int}> $lines */
    private function persist(array $lines): void
    {
        $this->session->put(self::SESSION_KEY, $lines);
        $this->hydrated = null;
    }
}
