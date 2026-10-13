<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SetAreaStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Existing Admin middleware enforces access.
    }

    public function rules(): array
    {
        return ['status' => ['required', Rule::in(['active', 'inactive'])]];
    }

    public function messages(): array
    {
        return ['status.required' => 'Vui lòng chọn trạng thái khu vực.', 'status.in' => 'Trạng thái khu vực phải là active hoặc inactive.'];
    }
}
