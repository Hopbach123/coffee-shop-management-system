<?php

namespace App\Http\Requests\Order;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOrderItemStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user('web');

        return $user !== null && in_array($user->role, ['admin', 'staff'], true) && $user->status === 'active';
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'string', 'in:preparing,ready,served'],
        ];
    }
}
