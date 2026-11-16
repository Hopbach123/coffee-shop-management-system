<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CustomerOrderRequestItem extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['quantity' => 'integer', 'unit_price' => 'decimal:2', 'topping_amount' => 'decimal:2', 'subtotal' => 'decimal:2'];
    }

    public function toppings(): HasMany
    {
        return $this->hasMany(CustomerOrderRequestItemTopping::class, 'request_item_id');
    }
}
