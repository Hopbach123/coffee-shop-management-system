<?php

namespace App\Models;

use App\Models\Base\Reservation as BaseReservation;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Reservation extends BaseReservation
{
    protected $fillable = [
        'customer_id',
        'table_id',
        'customer_name',
        'customer_phone',
        'guest_count',
        'reservation_start_at',
        'reservation_end_at',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'guest_count' => 'integer',
            'reservation_start_at' => 'datetime',
            'reservation_end_at' => 'datetime',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by')->withTrashed();
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id')->withTrashed();
    }

    public function cafeTable(): BelongsTo
    {
        return $this->belongsTo(CafeTable::class, 'table_id')->withTrashed();
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'reservation_id');
    }
}
