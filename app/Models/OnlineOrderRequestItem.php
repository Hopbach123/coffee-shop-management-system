<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OnlineOrderRequestItem extends Model
{
    protected $table = 'online_order_request_items';

    protected $fillable = [
        'request_id',
        'variant_id',
        'quantity',
        'note',
        'product_name',
        'variant_name',
        'unit_price',
    ];

    protected function casts(): array
    {
        return [
            'unit_price' => 'decimal:2',
            'quantity' => 'integer',
        ];
    }

    public function request(): BelongsTo
    {
        return $this->belongsTo(OnlineOrderRequest::class, 'request_id');
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id')->withTrashed();
    }

    public function toppings(): HasMany
    {
        return $this->hasMany(OnlineOrderRequestItemTopping::class, 'request_item_id');
    }
}
