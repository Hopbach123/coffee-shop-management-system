<?php

namespace App\Models;

use App\Models\Base\OrderPromotion as BaseOrderPromotion;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderPromotion extends BaseOrderPromotion
{
    protected $fillable = [
        'order_id',
        'promotion_id',
    ];

    protected function casts(): array
    {
        return [
            'discount_amount' => 'decimal:2',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function promotion(): BelongsTo
    {
        return $this->belongsTo(Promotion::class, 'promotion_id')->withTrashed();
    }
}
