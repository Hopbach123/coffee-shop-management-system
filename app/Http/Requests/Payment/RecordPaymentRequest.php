<?php

namespace App\Http\Requests\Payment;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RecordPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $actor = $this->user('web');

        return $actor !== null && $actor->status === 'active'
            && in_array($actor->role, ['admin', 'staff'], true);
    }

    public function rules(): array
    {
        return [
            'operation_key' => ['sometimes', 'required', 'string', 'min:16', 'max:64', 'regex:/^[a-zA-Z0-9_-]+$/'],
            'payment_method' => ['required', Rule::in(['cash', 'bank_transfer'])],
            'received_confirmation' => ['required', 'accepted'],
            'transaction_code' => ['nullable', 'string', 'max:100'],
            'received_amount' => [$this->has('operation_key') ? 'required' : 'nullable', 'string', 'regex:/\A\d{1,13}(?:\.\d{1,2})?\z/'],
        ];
    }

    public function messages(): array
    {
        return ['received_confirmation.accepted' => 'Vui lòng xác nhận đã nhận đủ tiền trước khi ghi nhận thanh toán.'];
    }
}
