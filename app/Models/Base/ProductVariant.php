<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\Base;

use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductRecipe;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class ProductVariant
 * 
 * @property int $id
 * @property int $product_id
 * @property string $name
 * @property string|null $sku
 * @property float $price
 * @property string $status
 * @property string|null $deleted_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property Product $product
 * @property Collection|OrderItem[] $order_items
 * @property Collection|ProductRecipe[] $product_recipes
 *
 * @package App\Models\Base
 */
class ProductVariant extends Model
{
	use SoftDeletes;
	protected $table = 'product_variants';

	protected $casts = [
		'product_id' => 'int',
		'price' => 'float'
	];

	public function product()
	{
		return $this->belongsTo(Product::class);
	}

	public function order_items()
	{
		return $this->hasMany(OrderItem::class, 'variant_id');
	}

	public function product_recipes()
	{
		return $this->hasMany(ProductRecipe::class, 'variant_id');
	}
}
