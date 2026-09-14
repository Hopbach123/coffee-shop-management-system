<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\Base;

use App\Models\Order;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Payment
 * 
 * @property int $id
 * @property int $order_id
 * @property string $payment_code
 * @property string $payment_method
 * @property float $amount
 * @property string|null $transaction_code
 * @property string $status
 * @property Carbon|null $paid_at
 * @property int $created_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property User $user
 * @property Order $order
 *
 * @package App\Models\Base
 */
class Payment extends Model
{
	protected $table = 'payments';

	protected $casts = [
		'order_id' => 'int',
		'amount' => 'float',
		'paid_at' => 'datetime',
		'created_by' => 'int'
	];

	public function user()
	{
		return $this->belongsTo(User::class, 'created_by');
	}

	public function order()
	{
		return $this->belongsTo(Order::class);
	}
}
