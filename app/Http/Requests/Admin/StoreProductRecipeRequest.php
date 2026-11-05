<?php

namespace App\Http\Requests\Admin;

use Illuminate\Validation\Rule;

class StoreProductRecipeRequest extends UpdateProductRecipeRequest
{
    public function rules(): array
    {
        return parent::rules() + [
            'ingredient_id' => [
                'required', 'integer',
                Rule::exists('ingredients', 'id')->whereNull('deleted_at')->where('status', 'active'),
                Rule::unique('product_recipes', 'ingredient_id')->where('variant_id', $this->route('product_variant')->id),
            ],
        ];
    }

    public function messages(): array
    {
        return parent::messages() + [
            'ingredient_id.required' => 'Vui lòng chọn nguyên liệu.',
            'ingredient_id.integer' => 'Nguyên liệu không hợp lệ.',
            'ingredient_id.exists' => 'Nguyên liệu không tồn tại, đã xóa hoặc ngừng dùng.',
            'ingredient_id.unique' => 'Nguyên liệu đã có trong công thức; hãy sửa định lượng ở dòng hiện tại.',
        ];
    }
}
