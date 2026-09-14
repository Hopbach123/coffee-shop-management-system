<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\Base;

use App\Models\Ingredient;
use App\Models\ProductVariant;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class ProductRecipe
 * 
 * @property int $id
 * @property int $variant_id
 * @property int $ingredient_id
 * @property float $quantity
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property Ingredient $ingredient
 * @property ProductVariant $product_variant
 *
 * @package App\Models\Base
 */
class ProductRecipe extends Model
{
	protected $table = 'product_recipes';

	protected $casts = [
		'variant_id' => 'int',
		'ingredient_id' => 'int',
		'quantity' => 'float'
	];

	public function ingredient()
	{
		return $this->belongsTo(Ingredient::class);
	}

	public function product_variant()
	{
		return $this->belongsTo(ProductVariant::class, 'variant_id');
	}
}
