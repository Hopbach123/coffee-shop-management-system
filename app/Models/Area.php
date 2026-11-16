<?php

namespace App\Models;

use App\Models\Base\Area as BaseArea;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Area extends BaseArea
{
    public function scopeMatchingFilters(Builder $query, string $search, string $status): void
    {
        $query->when($search !== '', fn ($query) => $query->where('name', 'like', '%'.$search.'%'))
            ->when($status !== '', fn ($query) => $query->where('status', $status));
    }

    public function scopeWithTableCounts(Builder $query): void
    {
        $query->withCount([
            'cafeTables as tables_count',
            'cafeTables as available_count' => fn ($query) => $query->where('status', 'available'),
            'cafeTables as occupied_count' => fn ($query) => $query->where('status', 'occupied'),
            'cafeTables as maintenance_count' => fn ($query) => $query->where('status', 'maintenance'),
        ]);
    }

    protected $fillable = [
        'name',
        'description',
    ];

    public function cafeTables(): HasMany
    {
        return $this->hasMany(CafeTable::class, 'area_id');
    }
}
