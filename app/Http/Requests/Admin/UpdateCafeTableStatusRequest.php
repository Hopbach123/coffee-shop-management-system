<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCafeTableStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Existing Admin middleware enforces access.
    }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in(['available', 'occupied', 'maintenance'])],
            'expected_status' => ['required', Rule::in(['available', 'occupied', 'maintenance'])],
        ];
    }

    public function messages(): array
    {
        return [
            'status.required' => 'Vui lòng chọn trạng thái bàn.',
            'status.in' => 'Trạng thái bàn không hợp lệ.',
            'expected_status.required' => 'Thiếu trạng thái ban đầu. Vui lòng tải lại danh sách.',
            'expected_status.in' => 'Trạng thái ban đầu không hợp lệ. Vui lòng tải lại danh sách.',
        ];
    }
}
