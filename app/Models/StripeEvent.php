<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;

/**
 * A Stripe webhook event we accepted. The primary key is Stripe's event ID, so
 * redelivered events map to the same row.
 *
 * @property array<string, mixed> $payload
 */
class StripeEvent extends Model
{
    use Prunable;

    /** Processed events are kept this long as an audit trail. */
    public const PRUNE_PROCESSED_AFTER_DAYS = 90;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['id', 'type', 'object_id', 'payload'];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'attempts' => 'integer',
            'processed_at' => 'datetime',
        ];
    }

    public function isProcessed(): bool
    {
        return $this->processed_at !== null;
    }

    /** The event's `data.object`, e.g. the checkout session. */
    public function object(): array
    {
        return $this->payload['data']['object'] ?? [];
    }

    /**
     * Only processed events are pruned: an unprocessed one needs looking at.
     *
     * @return Builder<self>
     */
    public function prunable(): Builder
    {
        return static::query()
            ->whereNotNull('processed_at')
            ->where('processed_at', '<=', now()->subDays(self::PRUNE_PROCESSED_AFTER_DAYS));
    }
}
