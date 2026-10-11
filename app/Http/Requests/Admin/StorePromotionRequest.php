<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePromotionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('code'))) {
            $this->merge(['code' => mb_strtoupper(trim($this->input('code')))]);
        }

        if ($this->input('discount_type') === 'fixed') {
            $this->merge(['max_discount' => null]);
        }
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:50', Rule::unique('promotions', 'code')],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'discount_type' => ['required', Rule::in(['percentage', 'fixed'])],
            'discount_value' => ['required', 'numeric', 'min:0', 'max:9999999999.99', 'decimal:0,2'],
            'max_discount' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99', 'decimal:0,2'],
            'minimum_order' => ['required', 'numeric', 'min:0', 'max:9999999999.99', 'decimal:0,2'],
            'start_at' => ['required', 'date'],
            'end_at' => ['required', 'date', 'after:start_at'],
            'usage_limit' => ['nullable', 'integer', 'min:1', 'max:2147483647'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ];
    }
}
