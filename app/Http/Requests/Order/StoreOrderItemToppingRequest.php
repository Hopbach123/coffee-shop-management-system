<?php

namespace App\Http\Requests\Order;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOrderItemToppingRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user('web');

        return $user?->canOperateSales() ?? false;
    }

    public function rules(): array
    {
        return [
            'topping_id' => ['required', 'integer', Rule::exists('toppings', 'id')->where('status', 'available')->whereNull('deleted_at')],
            'quantity' => ['required', 'integer', 'min:1', 'max:2147483647'],
        ];
    }

    public function attributes(): array
    {
        return [
            'topping_id' => 'topping',
            'quantity' => 'số lượng topping',
        ];
    }
}
