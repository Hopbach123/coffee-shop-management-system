<?php

namespace App\Models;

use App\Models\Base\Customer as BaseCustomer;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends BaseCustomer
{
    protected $fillable = [
        'name',
        'phone',
        'email',
        'birthday',
    ];

    protected function casts(): array
    {
        return [
            'birthday' => 'date',
        ];
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'customer_id');
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class, 'customer_id');
    }
}
