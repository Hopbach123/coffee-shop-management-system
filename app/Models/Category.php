<?php

namespace App\Models;

use App\Models\Base\Category as BaseCategory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends BaseCategory
{
    protected $fillable = [
        'name',
        'description',
        'image',
        'display_order',
    ];

    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'category_id');
    }
}
