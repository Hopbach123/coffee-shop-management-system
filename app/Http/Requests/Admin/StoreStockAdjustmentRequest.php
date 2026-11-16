<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use Throwable;

class StoreStockAdjustmentRequest extends FormRequest
{
    private array $operation = [];

    public function authorize(): bool
    {
        $user = $this->user('web');

        return $user?->role === 'admin' && $user->status === 'active' && ! $user->trashed();
    }

    public function rules(): array
    {
        return [
            'mode' => ['required', Rule::in(['increase', 'decrease', 'waste', 'stocktake'])],
            'quantity' => ['bail', 'required', 'string', 'regex:/\A\d{1,9}(?:\.\d{1,3})?\z/', 'numeric', $this->input('mode') === 'stocktake' ? 'min:0' : 'min:0.001', 'max:999999999.999'],
            'note' => ['required', 'string', 'max:1000'],
            'operation_token' => ['required', 'string', 'max:4096'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->has('operation_token')) {
                return;
            }
            try {
                $data = json_decode(Crypt::decryptString($this->input('operation_token')), true, 8, JSON_THROW_ON_ERROR);
                if (! is_array($data) || ($data['purpose'] ?? null) !== 'stock_adjustment'
                    || ($data['ingredient'] ?? null) !== $this->route('ingredient')->id
                    || ($data['actor'] ?? null) !== $this->user('web')->id
                    || ! is_string($data['unit'] ?? null) || ! is_string($data['stock'] ?? null)
                    || ! is_int($data['ledger'] ?? null) || ! is_string($data['key'] ?? null)
                    || ! preg_match('/\A[1-9]\d{0,17}\z/', $data['key'])) {
                    throw new \UnexpectedValueException;
                }
                $this->operation = $data;
            } catch (Throwable) {
                $validator->errors()->add('operation_token', 'Phiếu không hợp lệ. Vui lòng mở phiếu mới.');
            }
        }];
    }

    public function operation(): array
    {
        return $this->operation;
    }

    public function messages(): array
    {
        return ['quantity.regex' => 'Nhập số tối đa 9 chữ số nguyên và 3 chữ số thập phân.',
            'quantity.min' => 'Lượng điều chỉnh phải dương; riêng tồn kiểm kê được bằng 0.',
            'note.required' => 'Vui lòng ghi lý do điều chỉnh hoặc hao hụt.',
            'note.max' => 'Lý do tối đa 1000 ký tự.'];
    }
}
