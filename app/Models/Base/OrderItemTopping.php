<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\Base;

use App\Models\OrderItem;
use App\Models\Topping;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class OrderItemTopping
 * 
 * @property int $id
 * @property int $order_item_id
 * @property int $topping_id
 * @property string $topping_name
 * @property float $price
 * @property int $quantity
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property OrderItem $order_item
 * @property Topping $topping
 *
 * @package App\Models\Base
 */
class OrderItemTopping extends Model
{
	protected $table = 'order_item_toppings';

	protected $casts = [
		'order_item_id' => 'int',
		'topping_id' => 'int',
		'price' => 'float',
		'quantity' => 'int'
	];

	public function order_item()
	{
		return $this->belongsTo(OrderItem::class);
	}

	public function topping()
	{
		return $this->belongsTo(Topping::class);
	}
}
