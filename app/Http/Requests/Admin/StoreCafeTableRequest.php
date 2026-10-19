<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCafeTableRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Existing Admin middleware enforces access.
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('table_code'))) {
            $this->merge(['table_code' => trim($this->input('table_code'))]);
        }
    }

    public function rules(): array
    {
        return [
            'area_id' => ['required', 'integer', Rule::exists('areas', 'id')->where('status', 'active')->whereNull('deleted_at')],
            'table_code' => ['required', 'string', 'max:20', Rule::unique('cafe_tables', 'table_code')],
            'table_name' => ['nullable', 'string', 'max:100'],
            'capacity' => ['required', 'integer', 'between:1,2147483647'],
        ];
    }

    public function messages(): array
    {
        return [
            'area_id.required' => 'Vui lòng chọn khu vực.',
            'area_id.integer' => 'Khu vực không hợp lệ.',
            'area_id.exists' => 'Khu vực phải tồn tại, đang hoạt động và chưa xóa.',
            'table_code.required' => 'Vui lòng nhập mã bàn.',
            'table_code.string' => 'Mã bàn phải là chuỗi ký tự.',
            'table_code.max' => 'Mã bàn không được vượt quá 20 ký tự.',
            'table_code.unique' => 'Mã bàn này đã được sử dụng.',
            'table_name.string' => 'Tên bàn phải là chuỗi ký tự.',
            'table_name.max' => 'Tên bàn không được vượt quá 100 ký tự.',
            'capacity.required' => 'Vui lòng nhập sức chứa.',
            'capacity.integer' => 'Sức chứa phải là số nguyên.',
            'capacity.between' => 'Sức chứa phải từ 1 đến 2147483647 theo giới hạn dữ liệu.',
        ];
    }
}
