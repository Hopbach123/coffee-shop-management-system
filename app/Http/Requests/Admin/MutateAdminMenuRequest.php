<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class MutateAdminMenuRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('web')?->role === 'admin';
    }

    public function rules(): array
    {
        return [
            'expected_fingerprint' => ['required', 'string', 'regex:/\A[a-f0-9]{64}\z/'],
            'is_active' => $this->isMethod('DELETE') ? ['prohibited'] : ['required', 'boolean'],
            'confirm_delete' => $this->isMethod('DELETE') ? ['accepted'] : ['prohibited'],
            'parent_id' => ['prohibited'], 'sort_order' => ['prohibited'], 'permission_key' => ['prohibited'],
            'route_name' => ['prohibited'], 'type' => ['prohibited'], 'name' => ['prohibited'], 'icon' => ['prohibited'],
            'id' => ['prohibited'], 'is_system' => ['prohibited'], 'created_at' => ['prohibited'],
        ];
    }

    public function messages(): array
    {
        return ['confirm_delete.accepted' => 'Vui lòng xác nhận xóa menu.',
            'is_active.required' => 'Vui lòng chọn trạng thái mong muốn.',
            'is_active.boolean' => 'Trạng thái phải là 0 hoặc 1.',
            'expected_fingerprint.required' => 'Vui lòng tải lại trang trước khi thao tác.',
            'prohibited' => 'Không được thay đổi trường :attribute trong thao tác này.'];
    }
}
