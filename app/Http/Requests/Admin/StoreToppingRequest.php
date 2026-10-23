<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreToppingRequest extends FormRequest
{
    public function authorize(): bool
    {
        // The Admin route group enforces auth:web and role:admin.
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'price' => ['required', 'numeric', 'min:0', 'max:9999999999.99', 'decimal:0,2'],
            'status' => ['required', Rule::in(['available', 'unavailable'])],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Vui lòng nhập tên topping.',
            'name.max' => 'Tên topping không được vượt quá 255 ký tự.',
            'price.required' => 'Vui lòng nhập giá bán.',
            'price.numeric' => 'Giá bán phải là số.',
            'price.min' => 'Giá bán không được âm.',
            'price.max' => 'Giá bán vượt quá giới hạn cho phép.',
            'price.decimal' => 'Giá bán chỉ được có tối đa hai chữ số thập phân.',
            'status.required' => 'Vui lòng chọn trạng thái.',
            'status.in' => 'Trạng thái phải là available hoặc unavailable.',
        ];
    }
}
