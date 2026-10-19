<?php

namespace App\Http\Requests\Order;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOrderItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user('web');

        return $user !== null && $user->role === 'staff' && $user->status === 'active';
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('note'))) {
            $this->merge(['note' => trim($this->input('note'))]);
        }
    }

    public function rules(): array
    {
        return [
            'variant_id' => ['required', 'integer', Rule::exists('product_variants', 'id')->where('status', 'available')->whereNull('deleted_at')],
            'quantity' => ['required', 'integer', 'min:1', 'max:2147483647'],
            'note' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function attributes(): array
    {
        return [
            'variant_id' => 'biến thể',
            'quantity' => 'số lượng',
            'note' => 'ghi chú món',
        ];
    }
}
