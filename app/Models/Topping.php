<?php

namespace App\Models;

use App\Models\Base\Topping as BaseTopping;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Topping extends BaseTopping
{
    protected $fillable = [
        'name',
        'price',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
        ];
    }

    public function orderItemToppings(): HasMany
    {
        return $this->hasMany(OrderItemTopping::class, 'topping_id');
    }
}
