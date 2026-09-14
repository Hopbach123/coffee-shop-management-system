<?php

namespace App\Models;

use App\Models\Base\ProductRecipe as BaseProductRecipe;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductRecipe extends BaseProductRecipe
{
    protected $fillable = [
        'variant_id',
        'ingredient_id',
        'quantity',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:3',
        ];
    }

    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id')->withTrashed();
    }

    public function ingredient(): BelongsTo
    {
        return $this->belongsTo(Ingredient::class, 'ingredient_id')->withTrashed();
    }
}
