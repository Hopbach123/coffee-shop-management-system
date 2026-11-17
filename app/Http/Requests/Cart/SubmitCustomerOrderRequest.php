<?php

namespace App\Http\Requests\Cart;

use Illuminate\Foundation\Http\FormRequest;

class SubmitCustomerOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        foreach (['customer_name', 'customer_phone', 'customer_address', 'note'] as $field) {
            if (is_string($this->input($field))) {
                $this->merge([$field => trim($this->input($field))]);
            }
        }
    }

    public function rules(): array
    {
        return [
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_phone' => ['required', 'string', 'max:20', 'regex:/\A(?=.*[0-9])[0-9+() -]+\z/'],
            'customer_address' => ['required', 'string', 'max:500'],
            'note' => ['nullable', 'string', 'max:500'],
            'payment_option' => ['required', 'in:bank_transfer_prepaid,cod'],
            'submission_key' => ['required', 'string', 'size:64'],
        ];
    }
}
