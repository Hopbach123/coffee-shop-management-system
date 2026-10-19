<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductRecipeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('web')?->role === 'admin';
    }

    public function rules(): array
    {
        return ['quantity' => ['required', 'numeric', 'min:0.001', 'max:999999999.999', 'decimal:0,3']];
    }

    public function messages(): array
    {
        return [
            'quantity.required' => 'Vui lòng nhập định lượng.',
            'quantity.numeric' => 'Định lượng phải là số.',
            'quantity.min' => 'Định lượng phải lớn hơn 0 (tối thiểu 0.001).',
            'quantity.max' => 'Định lượng không được vượt quá 999999999.999.',
            'quantity.decimal' => 'Định lượng chỉ có tối đa 3 chữ số thập phân.',
        ];
    }
}
