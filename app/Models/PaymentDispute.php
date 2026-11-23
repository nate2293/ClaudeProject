<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A card dispute (chargeback). The primary key is the provider's dispute ID, so
 * repeated notifications update the same row.
 */
class PaymentDispute extends Model
{
    /** Provider statuses after which nothing more can be done. */
    public const CLOSED_STATUSES = ['won', 'lost', 'warning_closed'];

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['id', 'order_id', 'payment_reference', 'amount', 'currency', 'reason', 'status', 'evidence_due_by', 'closed_at'];

    protected function casts(): array
    {
        return ['amount' => 'integer', 'evidence_due_by' => 'datetime', 'closed_at' => 'datetime'];
    }

    /** @return BelongsTo<Order, $this> */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function isOpen(): bool
    {
        return ! in_array($this->status, self::CLOSED_STATUSES, true);
    }
}
