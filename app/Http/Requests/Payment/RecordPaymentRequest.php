<?php

namespace App\Http\Requests\Payment;

use Illuminate\Foundation\Http\FormRequest;

class RecordPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('web')?->canOperateSales() ?? false;
    }

    public function rules(): array
    {
        return [
            'payment_method' => ['required', 'in:cash,bank_transfer'],
            'operation_key' => ['required', 'string', 'min:16', 'max:64', 'regex:/^[a-zA-Z0-9_-]+$/'],
            'received_amount' => ['required', 'string', 'regex:/^(0|[1-9]\d{0,12})(\.\d{1,2})?$/'],
            'transaction_code' => ['nullable', 'required_if:payment_method,bank_transfer', 'string', 'max:100'],
            'received_confirmation' => ['required', 'accepted'],
        ];
    }
}
