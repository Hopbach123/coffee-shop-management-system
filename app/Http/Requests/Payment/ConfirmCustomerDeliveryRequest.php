<?php

namespace App\Http\Requests\Payment;

use Illuminate\Foundation\Http\FormRequest;

class ConfirmCustomerDeliveryRequest extends FormRequest
{
    public function authorize(): bool
    {
        $actor = $this->user('web');

        return $actor !== null && $actor->status === 'active' && in_array($actor->role, ['admin', 'staff'], true);
    }

    public function rules(): array
    {
        $cod = $this->route('customerRequest')?->payment_option === 'cod';

        $rules = [
            'delivery_confirmation' => ['required', 'accepted'],
        ];
        if ($cod) {
            $rules['received_confirmation'] = ['required', 'accepted'];
            $rules['payment_method'] = ['required', 'in:cash,bank_transfer'];
            $rules['transaction_code'] = ['nullable', 'string', 'max:100'];
        }

        return $rules;
    }
}
