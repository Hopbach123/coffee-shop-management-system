<?php

namespace App\Http\Requests\Order;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOrderItemToppingQuantityRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user('web');

        return $user?->canOperateSales() ?? false;
    }

    public function rules(): array
    {
        return [
            'quantity' => ['required', 'integer', 'min:1', 'max:2147483647'],
        ];
    }
}
