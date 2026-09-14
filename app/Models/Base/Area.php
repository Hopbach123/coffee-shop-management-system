<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\Base;

use App\Models\CafeTable;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class Area
 * 
 * @property int $id
 * @property string $name
 * @property string|null $description
 * @property string $status
 * @property string|null $deleted_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property Collection|CafeTable[] $cafe_tables
 *
 * @package App\Models\Base
 */
class Area extends Model
{
	use SoftDeletes;
	protected $table = 'areas';

	public function cafe_tables()
	{
		return $this->hasMany(CafeTable::class);
	}
}
