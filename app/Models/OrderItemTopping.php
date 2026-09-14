<?php

namespace App\Models;

use App\Models\Base\OrderItemTopping as BaseOrderItemTopping;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItemTopping extends BaseOrderItemTopping
{
    protected $fillable = [
        'topping_id',
        'quantity',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'quantity' => 'integer',
        ];
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class, 'order_item_id');
    }

    public function topping(): BelongsTo
    {
        return $this->belongsTo(Topping::class, 'topping_id')->withTrashed();
    }
}
