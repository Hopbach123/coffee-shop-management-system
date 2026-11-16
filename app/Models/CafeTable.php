<?php

namespace App\Models;

use App\Models\Base\CafeTable as BaseCafeTable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CafeTable extends BaseCafeTable
{
    public function scopeForFloorPlan(Builder $query, array $filters): void
    {
        $query->whereHas('area', fn ($area) => $area->whereNull('areas.deleted_at'))
            ->when(($filters['search'] ?? '') !== '', fn ($query) => $query->where(fn ($search) => $search
                ->where('table_code', 'like', '%'.$filters['search'].'%')
                ->orWhere('table_name', 'like', '%'.$filters['search'].'%')))
            ->when($filters['area_id'] ?? null, fn ($query, $id) => $query->where('area_id', $id))
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status));
    }

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
