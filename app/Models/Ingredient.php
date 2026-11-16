<?php

namespace App\Models;

use App\Models\Base\Ingredient as BaseIngredient;
use Illuminate\Database\Eloquent\Builder;
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

    public function scopeStockLevel(Builder $query, string $level): Builder
    {
        $query->where('status', 'active');

        return match ($level) {
            'out' => $query->where('current_stock', 0),
            'low' => $query->where('current_stock', '>', 0)->whereColumn('current_stock', '<', 'minimum_stock'),
            'sufficient' => $query->where('current_stock', '>', 0)->whereColumn('current_stock', '>=', 'minimum_stock'),
        };
    }

    public function getStockLevelAttribute(): string
    {
        if ($this->status !== 'active') {
            return 'inactive';
        }
        $stock = (int) str_replace('.', '', $this->current_stock);
        $minimum = (int) str_replace('.', '', $this->minimum_stock);

        return $stock === 0 ? 'out' : ($stock < $minimum ? 'low' : 'sufficient');
    }
}
