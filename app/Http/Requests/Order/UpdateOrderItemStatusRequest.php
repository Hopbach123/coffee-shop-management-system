<?php

namespace App\Http\Requests\Order;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOrderItemStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user('web');

        return $user?->canOperateSales() ?? false;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'string', 'in:preparing,ready,served'],
        ];
    }
}
