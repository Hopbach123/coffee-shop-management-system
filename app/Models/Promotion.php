<?php

namespace App\Models;

use App\Models\Base\Promotion as BasePromotion;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Promotion extends BasePromotion
{
    protected $fillable = [
        'code',
        'name',
        'description',
        'discount_type',
        'discount_value',
        'max_discount',
        'minimum_order',
        'start_at',
        'end_at',
        'usage_limit',
    ];

    protected function casts(): array
    {
        return [
            'discount_value' => 'decimal:2',
            'max_discount' => 'decimal:2',
            'minimum_order' => 'decimal:2',
            'start_at' => 'datetime',
            'end_at' => 'datetime',
            'usage_limit' => 'integer',
            'used_count' => 'integer',
        ];
    }

    public function orderPromotions(): HasMany
    {
        return $this->hasMany(OrderPromotion::class, 'promotion_id');
    }
}
