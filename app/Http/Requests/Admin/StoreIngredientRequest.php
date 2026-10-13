<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreIngredientRequest extends FormRequest
{
    private bool $customUnit = false;

    protected function prepareForValidation(): void
    {
        $this->customUnit = $this->input('unit') === 'other';
        if ($this->customUnit) {
            $unit = $this->input('custom_unit');
            $this->merge(['unit' => is_string($unit) ? mb_strtolower(preg_replace('/\s+/u', ' ', trim($unit))) : $unit]);
        }
    }

    public function authorize(): bool
    {
        return $this->user('web')?->role === 'admin';
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'unit' => $this->customUnit
                ? ['required', 'string', 'max:50', 'regex:/\A(?=.*\p{L})[\p{L}\p{M}\p{N} .\/\-]+\z/u', Rule::notIn(['other'])]
                : ['required', 'string', Rule::in(array_keys(config('inventory.units')))],
            'minimum_stock' => ['required', 'numeric', 'min:0', 'max:999999999.999', 'decimal:0,3'],
            'cost_price' => ['required', 'numeric', 'min:0', 'max:9999999999.99', 'decimal:0,2'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ];
    }

    public function attributes(): array
    {
        return ['name' => 'tên nguyên liệu', 'unit' => 'đơn vị tính', 'minimum_stock' => 'ngưỡng tồn tối thiểu', 'cost_price' => 'giá vốn', 'status' => 'trạng thái'];
    }

    public function messages(): array
    {
        return [
            'required' => 'Vui lòng nhập :attribute.',
            'string' => ':Attribute phải là chuỗi ký tự.',
            'numeric' => ':Attribute phải là số.',
            'min' => ':Attribute không được âm.',
            'max' => ':Attribute vượt quá giới hạn cho phép (:max).',
            'decimal' => ':Attribute có số chữ số thập phân vượt quá giới hạn cho phép.',
            'status.in' => 'Trạng thái phải là active hoặc inactive.',
            'unit.in' => 'Vui lòng chọn đơn vị tính trong danh sách.',
            'unit.regex' => 'Đơn vị khác phải có chữ cái; chỉ dùng chữ, số, khoảng trắng, dấu chấm, gạch ngang hoặc dấu /.',
            'unit.not_in' => 'Vui lòng nhập tên đơn vị cụ thể thay cho other.',
        ];
    }
}
