<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreReservationRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Keep development-only until real MySQL concurrency and release acceptance pass.
        return app()->environment(['local', 'testing']);
    }

    protected function prepareForValidation(): void
    {
        foreach (['customer_name', 'customer_phone'] as $field) {
            if (is_string($this->input($field))) {
                $this->merge([$field => trim($this->input($field))]);
            }
        }
    }

    public function rules(): array
    {
        return [
            'customer_id' => ['nullable', 'integer', Rule::exists('customers', 'id')->where('status', 'active')->whereNull('deleted_at')],
            'table_id' => ['required', 'integer', Rule::exists('cafe_tables', 'id')->whereNull('deleted_at')],
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_phone' => ['required', 'string', 'max:20'],
            'guest_count' => ['required', 'integer', 'between:1,2147483647'],
            'reservation_start_at' => ['bail', 'required', 'string', 'date_format:Y-m-d\TH:i', 'after:now'],
            'reservation_end_at' => ['bail', 'required', 'string', 'date_format:Y-m-d\TH:i'],
            'note' => ['nullable', 'string', 'max:65535', function ($attribute, $value, $fail) {
                if (is_string($value) && strlen($value) > 65535) {
                    $fail('Ghi chú vượt giới hạn lưu trữ 65535 byte.');
                }
            }],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if (! $validator->errors()->has('reservation_start_at') && ! $validator->errors()->has('reservation_end_at')
                && $this->input('reservation_end_at') <= $this->input('reservation_start_at')) {
                $validator->errors()->add('reservation_end_at', 'Thời gian kết thúc phải sau thời gian bắt đầu.');
            }
        }];
    }

    public function attributes(): array
    {
        return [
            'customer_id' => 'khách hàng', 'table_id' => 'bàn', 'customer_name' => 'tên người đặt',
            'customer_phone' => 'số điện thoại', 'guest_count' => 'số khách',
            'reservation_start_at' => 'thời gian bắt đầu', 'reservation_end_at' => 'thời gian kết thúc', 'note' => 'ghi chú',
        ];
    }

    public function messages(): array
    {
        return [
            'required' => 'Vui lòng nhập/chọn :attribute.',
            'string' => ':attribute phải là chuỗi ký tự.',
            'integer' => ':attribute phải là số nguyên.',
            'max' => ':attribute không được vượt quá :max ký tự.',
            'exists' => ':attribute không còn hợp lệ hoặc đã ngừng hoạt động/xóa.',
            'guest_count.between' => 'Số khách phải từ 1 đến 2147483647 và không vượt sức chứa bàn.',
            'date_format' => ':attribute phải là ngày giờ hợp lệ theo định dạng YYYY-MM-DDTHH:mm (UTC).',
            'reservation_start_at.after' => 'Thời gian bắt đầu phải ở tương lai.',
            'reservation_end_at.after' => 'Thời gian kết thúc phải sau thời gian bắt đầu.',
        ];
    }
}
