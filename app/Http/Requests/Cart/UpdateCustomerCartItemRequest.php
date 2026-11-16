<?php

namespace App\Http\Requests\Cart;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCustomerCartItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['quantity' => ['required', 'integer', 'min:1', 'max:2147483647']];
    }

    public function attributes(): array
    {
        return ['quantity' => 'số lượng món'];
    }
}
