<?php

namespace App\Models;

use App\Models\Base\ProductVariant as BaseProductVariant;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductVariant extends BaseProductVariant
{
    protected $fillable = [
        'product_id',
        'name',
        'sku',
        'price',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id')->withTrashed();
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class, 'variant_id');
    }

    public function productRecipes(): HasMany
    {
        return $this->hasMany(ProductRecipe::class, 'variant_id');
    }
}
