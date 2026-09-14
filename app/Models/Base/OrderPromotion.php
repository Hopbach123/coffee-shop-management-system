<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\Base;

use App\Models\Order;
use App\Models\Promotion;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class OrderPromotion
 * 
 * @property int $id
 * @property int $order_id
 * @property int $promotion_id
 * @property string $promotion_code
 * @property float $discount_amount
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property Order $order
 * @property Promotion $promotion
 *
 * @package App\Models\Base
 */
class OrderPromotion extends Model
{
	protected $table = 'order_promotions';

	protected $casts = [
		'order_id' => 'int',
		'promotion_id' => 'int',
		'discount_amount' => 'float'
	];

	public function order()
	{
		return $this->belongsTo(Order::class);
	}

	public function promotion()
	{
		return $this->belongsTo(Promotion::class);
	}
}
