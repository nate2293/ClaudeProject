<?php

namespace App\Models;

use App\Enums\FulfilmentStatus;
use App\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class Order extends Model
{
    use Prunable;

    /**
     * Cancelled orders (checkout expired without payment, as confirmed by the
     * payment provider) are kept this long for support queries, then deleted.
     * Pending and processing orders are never pruned: reconciliation settles them.
     */
    private const PRUNE_CANCELLED_AFTER_DAYS = 30;

    protected $fillable = [
        'user_id', 'status', 'currency', 'subtotal', 'shipping', 'total', 'amount_paid', 'email', 'customer_name',
        'shipping_address', 'checkout_session_id', 'payment_reference', 'paid_at', 'review_reason',
        'vat_rate', 'vat_amount',
    ];

    protected $attributes = [
        'status' => 'pending',
        'fulfilment_status' => 'unfulfilled',
        'amount_refunded' => 0,
        'vat_amount' => 0,
    ];

    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'subtotal' => 'integer',
            'shipping' => 'integer',
            'total' => 'integer',
            'amount_paid' => 'integer',
            'amount_refunded' => 'integer',
            'vat_rate' => 'integer',
            'vat_amount' => 'integer',
            'fulfilment_status' => FulfilmentStatus::class,
            'shipping_address' => 'array',
            'paid_at' => 'datetime',
            'refunded_at' => 'datetime',
            'packed_at' => 'datetime',
            'shipped_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Order $order) {
            $order->reference ??= static::generateReference();
        });
    }

    public static function generateReference(): string
    {
        do {
            $reference = 'LL-'.Str::upper(Str::random(8));
        } while (static::where('reference', $reference)->exists());

        return $reference;
    }

    /** @return HasMany<OrderItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @param Builder<self> $query */
    public function scopePending(Builder $query): void
    {
        $query->where('status', OrderStatus::Pending);
    }

    /** Orders whose payment outcome is still unknown. @param Builder<self> $query */
    public function scopeUnsettled(Builder $query): void
    {
        $query->whereIn('status', OrderStatus::unsettled());
    }

    /** @return HasMany<PaymentDispute, $this> */
    public function disputes(): HasMany
    {
        return $this->hasMany(PaymentDispute::class);
    }

    public function isPaid(): bool
    {
        return $this->status === OrderStatus::Paid;
    }

    /** Paid for at some point, including orders since refunded. */
    public function wasPaidFor(): bool
    {
        return in_array($this->status, OrderStatus::paidFor(), true);
    }

    /** Amount that can still be refunded, in minor units. */
    public function refundableAmount(): int
    {
        return max(0, ($this->amount_paid ?? $this->total) - $this->amount_refunded);
    }

    /** Orders that have been paid for and still need packing or shipping. @param Builder<self> $query */
    public function scopeToFulfil(Builder $query): void
    {
        $query->whereIn('status', [OrderStatus::Paid, OrderStatus::PartiallyRefunded])
            ->where('fulfilment_status', '!=', FulfilmentStatus::Shipped);
    }

    /** @param Builder<self> $query */
    public function scopeNeedsReview(Builder $query): void
    {
        $query->whereNotNull('review_reason');
    }

    /**
     * Change the order's attributes only if it is still in one of the `$from`
     * statuses, in a single conditional UPDATE. Two concurrent callers (e.g. the
     * success redirect and the webhook) can't both apply a change; only the one
     * that gets `true` should run side effects such as a confirmation email.
     *
     * @param  list<OrderStatus>  $from
     * @param  array<string, mixed>  $attributes
     */
    public function transition(array $from, array $attributes): bool
    {
        // Run the values through the model's casts (enums, JSON, dates) before
        // handing them to a raw query-builder update.
        $prepared = Arr::only((new static)->forceFill($attributes)->getAttributes(), array_keys($attributes));

        $updated = static::query()
            ->whereKey($this->getKey())
            ->whereIn('status', $from)
            ->update($prepared);

        $this->refresh();

        return $updated === 1;
    }

    /**
     * Orders removed by `php artisan model:prune` (scheduled daily): only checkouts
     * the payment provider has confirmed expired unpaid, after a retention period.
     *
     * @return Builder<self>
     */
    public function prunable(): Builder
    {
        return static::query()
            ->where('status', OrderStatus::Cancelled)
            ->where('created_at', '<=', now()->subDays(self::PRUNE_CANCELLED_AFTER_DAYS));
    }
}
