<?php

namespace App\Http\Requests\Pos;

use Illuminate\Foundation\Http\FormRequest;

class StorePosCartItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        $actor = $this->user('web');

        return $actor !== null && $actor->status === 'active' && in_array($actor->role, ['admin', 'staff'], true);
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['quantity' => $this->input('quantity', 1)]);
    }

    public function rules(): array
    {
        return [
            'variant_id' => ['required', 'integer', 'min:1'],
            'quantity' => ['required', 'integer', 'min:1', 'max:2147483647'],
            'note' => ['nullable', 'string', 'max:500'],
            'toppings' => ['sometimes', 'array'],
            'toppings.*' => ['integer', 'min:0', 'max:2147483647'],
        ];
    }
}
