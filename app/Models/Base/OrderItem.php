<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\Base;

use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Topping;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class OrderItem
 * 
 * @property int $id
 * @property int $order_id
 * @property int $product_id
 * @property int $variant_id
 * @property string $product_name
 * @property string|null $variant_name
 * @property int $quantity
 * @property float $unit_price
 * @property float $topping_amount
 * @property float $subtotal
 * @property string|null $note
 * @property string $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property Order $order
 * @property Product $product
 * @property ProductVariant $product_variant
 * @property Collection|Topping[] $toppings
 *
 * @package App\Models\Base
 */
class OrderItem extends Model
{
	protected $table = 'order_items';

	protected $casts = [
		'order_id' => 'int',
		'product_id' => 'int',
		'variant_id' => 'int',
		'quantity' => 'int',
		'unit_price' => 'float',
		'topping_amount' => 'float',
		'subtotal' => 'float'
	];

	public function order()
	{
		return $this->belongsTo(Order::class);
	}

	public function product()
	{
		return $this->belongsTo(Product::class);
	}

	public function product_variant()
	{
		return $this->belongsTo(ProductVariant::class, 'variant_id');
	}

	public function toppings()
	{
		return $this->belongsToMany(Topping::class, 'order_item_toppings')
					->withPivot('id', 'topping_name', 'price', 'quantity')
					->withTimestamps();
	}
}
