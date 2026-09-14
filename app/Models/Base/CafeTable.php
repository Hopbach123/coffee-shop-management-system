<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\Base;

use App\Models\Area;
use App\Models\Order;
use App\Models\Reservation;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class CafeTable
 * 
 * @property int $id
 * @property int $area_id
 * @property string $table_code
 * @property string|null $table_name
 * @property int $capacity
 * @property string $status
 * @property string|null $qr_code
 * @property string|null $deleted_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property Area $area
 * @property Collection|Order[] $orders
 * @property Collection|Reservation[] $reservations
 *
 * @package App\Models\Base
 */
class CafeTable extends Model
{
	use SoftDeletes;
	protected $table = 'cafe_tables';

	protected $casts = [
		'area_id' => 'int',
		'capacity' => 'int'
	];

	public function area()
	{
		return $this->belongsTo(Area::class);
	}

	public function orders()
	{
		return $this->hasMany(Order::class, 'table_id');
	}

	public function reservations()
	{
		return $this->hasMany(Reservation::class, 'table_id');
	}
}
