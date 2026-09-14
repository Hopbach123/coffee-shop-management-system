<?php

namespace App\Models;

use App\Models\Base\Ingredient as BaseIngredient;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ingredient extends BaseIngredient
{
    protected $fillable = [
        'name',
        'unit',
        'minimum_stock',
        'cost_price',
    ];

    protected function casts(): array
    {
        return [
            'current_stock' => 'decimal:3',
            'minimum_stock' => 'decimal:3',
            'cost_price' => 'decimal:2',
        ];
    }

    public function inventoryTransactions(): HasMany
    {
        return $this->hasMany(InventoryTransaction::class, 'ingredient_id');
    }

    public function productRecipes(): HasMany
    {
        return $this->hasMany(ProductRecipe::class, 'ingredient_id');
    }
}
