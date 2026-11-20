<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerOrderRequestItemTopping extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['quantity' => 'integer', 'price' => 'decimal:2'];
    }
}
