<?php

namespace App\Http\Requests\Admin;

use App\Models\Ingredient;
use Illuminate\Validation\Validator;

class UpdateIngredientRequest extends StoreIngredientRequest
{
    public function after(): array
    {
        return [function (Validator $validator): void {
            $ingredient = $this->route('ingredient');
            if (! $ingredient instanceof Ingredient || $validator->errors()->has('unit') || $this->input('unit') === $ingredient->unit) {
                return;
            }

            if ((float) $ingredient->current_stock !== 0.0 || $ingredient->productRecipes()->exists() || $ingredient->inventoryTransactions()->exists()) {
                $validator->errors()->add('unit', 'Không thể đổi đơn vị khi đã có tồn kho, công thức hoặc lịch sử kho.');
            }
        }];
    }
}
