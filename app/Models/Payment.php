<?php

namespace App\Models;

use App\Models\Base\Payment as BasePayment;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends BasePayment
{
    protected $fillable = [
        'order_id',
        'payment_method',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'paid_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by')->withTrashed();
    }
}
