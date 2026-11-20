<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CustomerOrderRequest extends Model
{
    protected $guarded = ['id'];

    protected $hidden = ['access_token_hash', 'submission_key_hash', 'payload_hash'];

    protected function casts(): array
    {
        return ['quoted_subtotal' => 'decimal:2', 'delivery_fee' => 'decimal:2', 'quoted_total' => 'decimal:2',
            'access_expires_at' => 'datetime', 'handled_at' => 'datetime', 'dispatched_at' => 'datetime',
            'delivered_at' => 'datetime', 'order_id' => 'integer', 'handled_by' => 'integer'];
    }

    public function items(): HasMany
    {
        return $this->hasMany(CustomerOrderRequestItem::class, 'request_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function handler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by');
    }
}
