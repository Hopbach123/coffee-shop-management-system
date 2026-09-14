<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models\Base;

use App\Models\Ingredient;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class InventoryTransaction
 * 
 * @property int $id
 * @property int $ingredient_id
 * @property string $type
 * @property float $quantity
 * @property float $before_quantity
 * @property float $after_quantity
 * @property string|null $reference_type
 * @property int|null $reference_id
 * @property string|null $note
 * @property int $created_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property User $user
 * @property Ingredient $ingredient
 *
 * @package App\Models\Base
 */
class InventoryTransaction extends Model
{
	protected $table = 'inventory_transactions';

	protected $casts = [
		'ingredient_id' => 'int',
		'quantity' => 'float',
		'before_quantity' => 'float',
		'after_quantity' => 'float',
		'reference_id' => 'int',
		'created_by' => 'int'
	];

	public function user()
	{
		return $this->belongsTo(User::class, 'created_by');
	}

	public function ingredient()
	{
		return $this->belongsTo(Ingredient::class);
	}
}
