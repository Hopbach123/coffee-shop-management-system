<?php

namespace App\Models;

use App\Models\Base\Area as BaseArea;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Area extends BaseArea
{
    protected $fillable = [
        'name',
        'description',
    ];

    public function cafeTables(): HasMany
    {
        return $this->hasMany(CafeTable::class, 'area_id');
    }
}
