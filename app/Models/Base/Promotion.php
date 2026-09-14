<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\Base;

use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class Promotion
 * 
 * @property int $id
 * @property string $code
 * @property string $name
 * @property string|null $description
 * @property string $discount_type
 * @property float $discount_value
 * @property float|null $max_discount
 * @property float $minimum_order
 * @property Carbon $start_at
 * @property Carbon $end_at
 * @property int|null $usage_limit
 * @property int $used_count
 * @property string $status
 * @property string|null $deleted_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property Collection|Order[] $orders
 *
 * @package App\Models\Base
 */
class Promotion extends Model
{
	use SoftDeletes;
	protected $table = 'promotions';

	protected $casts = [
		'discount_value' => 'float',
		'max_discount' => 'float',
		'minimum_order' => 'float',
		'start_at' => 'datetime',
		'end_at' => 'datetime',
		'usage_limit' => 'int',
		'used_count' => 'int'
	];

	public function orders()
	{
		return $this->belongsToMany(Order::class, 'order_promotions')
					->withPivot('id', 'promotion_code', 'discount_amount')
					->withTimestamps();
	}
}
