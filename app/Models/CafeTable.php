<?php

namespace App\Models;

use App\Models\Base\CafeTable as BaseCafeTable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CafeTable extends BaseCafeTable
{
    protected $fillable = [
        'area_id',
        'table_code',
        'table_name',
        'capacity',
        'qr_code',
    ];

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class, 'area_id')->withTrashed();
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'table_id');
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class, 'table_id');
    }
}
