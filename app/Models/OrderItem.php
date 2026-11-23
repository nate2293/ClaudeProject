<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    protected $fillable = ['product_id', 'stock_level_id', 'restocked_quantity', 'product_name', 'colour', 'size', 'unit_price', 'quantity', 'line_total'];

    protected function casts(): array
    {
        return [
            'unit_price' => 'integer',
            'quantity' => 'integer',
            'line_total' => 'integer',
            'restocked_quantity' => 'integer',
        ];
    }

    /** Units that can still be put back into stock (e.g. after a return). */
    public function restockable(): int
    {
        return $this->stock_level_id ? max(0, $this->quantity - $this->restocked_quantity) : 0;
    }

    /** @return BelongsTo<Order, $this> */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
