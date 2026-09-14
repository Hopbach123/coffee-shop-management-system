<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\Base;

use App\Models\CafeTable;
use App\Models\Customer;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Promotion;
use App\Models\Reservation;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Order
 * 
 * @property int $id
 * @property string $order_code
 * @property int|null $table_id
 * @property int|null $customer_id
 * @property int $user_id
 * @property int|null $reservation_id
 * @property string $order_type
 * @property string $status
 * @property float $subtotal
 * @property float $discount_amount
 * @property float $tax_amount
 * @property float $total_amount
 * @property string|null $note
 * @property Carbon $ordered_at
 * @property Carbon|null $completed_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property Customer|null $customer
 * @property Reservation|null $reservation
 * @property CafeTable|null $cafe_table
 * @property User $user
 * @property Collection|OrderItem[] $order_items
 * @property Collection|Promotion[] $promotions
 * @property Collection|Payment[] $payments
 *
 * @package App\Models\Base
 */
class Order extends Model
{
	protected $table = 'orders';

	protected $casts = [
		'table_id' => 'int',
		'customer_id' => 'int',
		'user_id' => 'int',
		'reservation_id' => 'int',
		'subtotal' => 'float',
		'discount_amount' => 'float',
		'tax_amount' => 'float',
		'total_amount' => 'float',
		'ordered_at' => 'datetime',
		'completed_at' => 'datetime'
	];

	public function customer()
	{
		return $this->belongsTo(Customer::class);
	}

	public function reservation()
	{
		return $this->belongsTo(Reservation::class);
	}

	public function cafe_table()
	{
		return $this->belongsTo(CafeTable::class, 'table_id');
	}

	public function user()
	{
		return $this->belongsTo(User::class);
	}

	public function order_items()
	{
		return $this->hasMany(OrderItem::class);
	}

	public function promotions()
	{
		return $this->belongsToMany(Promotion::class, 'order_promotions')
					->withPivot('id', 'promotion_code', 'discount_amount')
					->withTimestamps();
	}

	public function payments()
	{
		return $this->hasMany(Payment::class);
	}
}
