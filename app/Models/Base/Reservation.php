<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\Base;

use App\Models\CafeTable;
use App\Models\Customer;
use App\Models\Order;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Reservation
 * 
 * @property int $id
 * @property int|null $customer_id
 * @property int|null $table_id
 * @property string $customer_name
 * @property string $customer_phone
 * @property int $guest_count
 * @property Carbon $reservation_start_at
 * @property Carbon $reservation_end_at
 * @property string|null $note
 * @property string $status
 * @property int|null $created_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property User|null $user
 * @property Customer|null $customer
 * @property CafeTable|null $cafe_table
 * @property Collection|Order[] $orders
 *
 * @package App\Models\Base
 */
class Reservation extends Model
{
	protected $table = 'reservations';

	protected $casts = [
		'customer_id' => 'int',
		'table_id' => 'int',
		'guest_count' => 'int',
		'reservation_start_at' => 'datetime',
		'reservation_end_at' => 'datetime',
		'created_by' => 'int'
	];

	public function user()
	{
		return $this->belongsTo(User::class, 'created_by');
	}

	public function customer()
	{
		return $this->belongsTo(Customer::class);
	}

	public function cafe_table()
	{
		return $this->belongsTo(CafeTable::class, 'table_id');
	}

	public function orders()
	{
		return $this->hasMany(Order::class);
	}
}
