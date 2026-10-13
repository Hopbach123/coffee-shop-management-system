<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreAreaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Existing Admin middleware enforces access.
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('name'))) {
            $this->merge(['name' => trim($this->input('name'))]);
        }
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Vui lòng nhập tên khu vực.',
            'name.string' => 'Tên khu vực phải là chuỗi ký tự.',
            'name.max' => 'Tên khu vực không được vượt quá 100 ký tự.',
            'description.string' => 'Mô tả phải là chuỗi ký tự.',
        ];
    }
}
