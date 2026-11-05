<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Validation\Validator;
use Throwable;

class StoreStockImportRequest extends FormRequest
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
            'quantity' => ['bail', 'required', 'string', 'regex:/\A\d{1,9}(?:\.\d{1,3})?\z/', 'numeric', 'min:0.001', 'max:999999999.999'],
            'note' => ['nullable', 'string', 'max:1000'],
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
                if (! is_array($data) || ($data['ingredient'] ?? null) !== $this->route('ingredient')->id
                    || ($data['actor'] ?? null) !== $this->user('web')->id
                    || ! is_string($data['unit'] ?? null)
                    || ! preg_match('/\A[1-9]\d{0,17}\z/', (string) ($data['key'] ?? ''))) {
                    throw new \UnexpectedValueException;
                }
                $this->operation = $data;
            } catch (Throwable) {
                $validator->errors()->add('operation_token', 'Phiếu nhập không hợp lệ. Vui lòng mở lại form nhập kho.');
            }
        }];
    }

    public function operation(): array
    {
        return $this->operation;
    }

    public function messages(): array
    {
        return [
            'quantity.required' => 'Vui lòng nhập số lượng.',
            'quantity.regex' => 'Số lượng phải là số dương, tối đa 9 chữ số nguyên và 3 chữ số thập phân.',
            'quantity.min' => 'Số lượng nhập phải lớn hơn 0.',
            'quantity.max' => 'Số lượng vượt giới hạn tồn kho.',
            'note.max' => 'Ghi chú tối đa 1000 ký tự.',
        ];
    }
}
