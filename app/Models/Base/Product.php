<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\Base;

use App\Models\Category;
use App\Models\OrderItem;
use App\Models\ProductVariant;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class Product
 * 
 * @property int $id
 * @property int $category_id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property string|null $image
 * @property string $status
 * @property bool $is_featured
 * @property string|null $deleted_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property Category $category
 * @property Collection|OrderItem[] $order_items
 * @property Collection|ProductVariant[] $product_variants
 *
 * @package App\Models\Base
 */
class Product extends Model
{
	use SoftDeletes;
	protected $table = 'products';

	protected $casts = [
		'category_id' => 'int',
		'is_featured' => 'bool'
	];

	public function category()
	{
		return $this->belongsTo(Category::class);
	}

	public function order_items()
	{
		return $this->hasMany(OrderItem::class);
	}

	public function product_variants()
	{
		return $this->hasMany(ProductVariant::class);
	}
}
