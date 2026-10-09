<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Access is enforced by the existing auth:web and role:admin route group.
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            // Match the signed INT column without introducing a non-negative business rule.
            'display_order' => ['sometimes', 'required', 'integer', 'between:-2147483648,2147483647'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Vui lòng nhập tên danh mục.',
            'name.string' => 'Tên danh mục phải là chuỗi ký tự.',
            'name.max' => 'Tên danh mục không được vượt quá 255 ký tự.',
            'description.string' => 'Mô tả phải là chuỗi ký tự.',
            'display_order.required' => 'Vui lòng nhập thứ tự hiển thị.',
            'display_order.integer' => 'Thứ tự hiển thị phải là số nguyên.',
            'display_order.between' => 'Thứ tự hiển thị phải từ -2147483648 đến 2147483647.',
            'status.required' => 'Vui lòng chọn trạng thái.',
            'status.in' => 'Trạng thái phải là active hoặc inactive.',
        ];
    }
}
