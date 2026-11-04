<?php

namespace App\Http\Requests\Admin;

use App\Services\AdminMenuManagementService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAdminMenuRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('web')?->role === 'admin';
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('name'))) {
            $this->merge(['name' => trim($this->input('name'))]);
        }
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'type' => ['required', Rule::in(['group', 'link'])],
            'route_name' => ['nullable', 'required_if:type,link', 'prohibited_if:type,group', 'string', 'max:150', Rule::in(app(AdminMenuManagementService::class)->allowedRoutes())],
            'icon' => ['nullable', 'string', Rule::in(config('admin_menu.icons'))],
            'parent_id' => ['nullable', 'integer', 'min:1'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:4294967295'],
            'permission_key' => ['prohibited'], 'is_active' => ['prohibited'],
            'is_system' => ['prohibited'], 'id' => ['prohibited'],
        ];
    }

    public function attributes(): array
    {
        return ['name' => 'tên menu', 'type' => 'loại menu', 'route_name' => 'route', 'parent_id' => 'menu cha',
            'sort_order' => 'thứ tự', 'icon' => 'biểu tượng'];
    }

    public function messages(): array
    {
        return ['required' => 'Vui lòng nhập :attribute.', 'max' => ':Attribute vượt giới hạn cho phép.',
            'in' => ':Attribute không nằm trong danh sách được phép.', 'integer' => ':Attribute phải là số nguyên.',
            'min' => ':Attribute nhỏ hơn giới hạn cho phép.', 'exists' => ':Attribute không tồn tại.',
            'required_if' => 'Vui lòng chọn :attribute cho menu liên kết.',
            'prohibited_if' => 'Menu nhóm không được có route.', 'prohibited' => 'Không được gán trường :attribute.'];
    }
}
