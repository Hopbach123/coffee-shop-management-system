<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\Base;

use App\Models\OrderItem;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class Topping
 * 
 * @property int $id
 * @property string $name
 * @property float $price
 * @property string $status
 * @property string|null $deleted_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property Collection|OrderItem[] $order_items
 *
 * @package App\Models\Base
 */
class Topping extends Model
{
	use SoftDeletes;
	protected $table = 'toppings';

	protected $casts = [
		'price' => 'float'
	];

	public function order_items()
	{
		return $this->belongsToMany(OrderItem::class, 'order_item_toppings')
					->withPivot('id', 'topping_name', 'price', 'quantity')
					->withTimestamps();
	}
}
