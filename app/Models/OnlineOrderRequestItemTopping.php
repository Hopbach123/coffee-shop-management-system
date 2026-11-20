<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OnlineOrderRequestItemTopping extends Model
{
    protected $table = 'online_order_request_item_toppings';

    protected $fillable = [
        'request_item_id',
        'topping_id',
        'quantity',
        'topping_name',
        'price',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'quantity' => 'integer',
        ];
    }

    public function requestItem(): BelongsTo
    {
        return $this->belongsTo(OnlineOrderRequestItem::class, 'request_item_id');
    }

    public function topping(): BelongsTo
    {
        return $this->belongsTo(Topping::class, 'topping_id')->withTrashed();
    }
}
