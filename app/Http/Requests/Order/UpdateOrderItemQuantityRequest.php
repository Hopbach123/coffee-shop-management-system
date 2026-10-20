<?php

namespace App\Http\Requests\Order;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOrderItemQuantityRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user('web');

        return $user !== null && $user->role === 'staff' && $user->status === 'active';
    }

    public function rules(): array
    {
        return [
            'quantity' => ['required', 'integer', 'min:1', 'max:2147483647'],
        ];
    }

    public function attributes(): array
    {
        return [
            'quantity' => 'số lượng',
        ];
    }
}
