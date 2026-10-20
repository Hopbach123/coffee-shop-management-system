<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ReorderAdminMenuRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('web')?->role === 'admin';
    }

    public function rules(): array
    {
        return [
            'expected_tree' => ['required', 'string', 'regex:/\A[a-f0-9]{64}\z/'],
            'items' => ['present', 'array'],
            'items.*' => ['required', 'array:id,parent_id,sort_order'],
            'items.*.id' => ['required', 'integer', 'min:1', 'distinct'],
            'items.*.parent_id' => ['present', 'nullable', 'integer', 'min:1'],
            'items.*.sort_order' => ['required', 'integer', 'min:0', 'max:4294967295'],
        ];
    }
}
