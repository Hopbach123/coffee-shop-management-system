<?php

namespace App\Http\Requests\Pos;

use Illuminate\Foundation\Http\FormRequest;

class CheckoutPosRequest extends FormRequest
{
    public function authorize(): bool
    {
        $actor = $this->user('web');

        return $actor !== null && $actor->status === 'active' && in_array($actor->role, ['admin', 'staff'], true);
    }

    public function rules(): array
    {
        return [
            'order_type' => ['required', 'in:dine_in,takeaway'],
            'table_id' => ['nullable', 'integer', 'required_if:order_type,dine_in'],
            'note' => ['nullable', 'string', 'max:500'],
            'payment_method' => ['required', 'in:cash,bank_transfer'],
            'received_confirmation' => ['required', 'accepted'],
            'transaction_code' => ['nullable', 'string', 'max:100'],
            'received_amount' => ['nullable', 'string', 'regex:/\A\d{1,13}(?:\.\d{1,2})?\z/'],
            'checkout_key' => ['required', 'string', 'size:64'],
        ];
    }
}
