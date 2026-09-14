<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\Base;

use App\Models\InventoryTransaction;
use App\Models\ProductRecipe;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class Ingredient
 * 
 * @property int $id
 * @property string $name
 * @property string $unit
 * @property float $current_stock
 * @property float $minimum_stock
 * @property float $cost_price
 * @property string $status
 * @property string|null $deleted_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property Collection|InventoryTransaction[] $inventory_transactions
 * @property Collection|ProductRecipe[] $product_recipes
 *
 * @package App\Models\Base
 */
class Ingredient extends Model
{
	use SoftDeletes;
	protected $table = 'ingredients';

	protected $casts = [
		'current_stock' => 'float',
		'minimum_stock' => 'float',
		'cost_price' => 'float'
	];

	public function inventory_transactions()
	{
		return $this->hasMany(InventoryTransaction::class);
	}

	public function product_recipes()
	{
		return $this->hasMany(ProductRecipe::class);
	}
}
