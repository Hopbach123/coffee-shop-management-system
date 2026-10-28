<?php

namespace App\Http\Requests\Order;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user('web');

        return $user?->canOperateSales() ?? false;
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('note'))) {
            $this->merge(['note' => trim($this->input('note'))]);
        }
    }

    public function rules(): array
    {
        return [
            'order_type' => ['required', 'string', Rule::in(['dine_in', 'takeaway'])],
            'table_id' => ['nullable', 'integer', Rule::exists('cafe_tables', 'id')->whereNull('deleted_at')],
            'customer_id' => ['nullable', 'integer', Rule::exists('customers', 'id')->where('status', 'active')->whereNull('deleted_at')],
            'note' => ['nullable', 'string', 'max:65535'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($this->input('order_type') === 'dine_in' && $this->input('table_id') === null) {
                $validator->errors()->add('table_id', 'Đơn tại chỗ phải chọn bàn.');
            }

            if ($this->input('order_type') === 'takeaway' && $this->input('table_id') !== null) {
                $validator->errors()->add('table_id', 'Đơn mang đi không được gắn bàn.');
            }
        }];
    }

    public function attributes(): array
    {
        return [
            'order_type' => 'loại đơn',
            'table_id' => 'bàn',
            'customer_id' => 'khách hàng',
            'note' => 'ghi chú',
        ];
    }
}
