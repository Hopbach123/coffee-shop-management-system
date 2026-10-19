<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PickupRequest extends Model
{
    protected $fillable = ['reference', 'customer_name', 'customer_phone', 'pickup_at', 'items', 'subtotal', 'note', 'status'];

    protected function casts(): array
    {
        return ['items' => 'array', 'pickup_at' => 'datetime', 'subtotal' => 'decimal:2'];
    }
}
