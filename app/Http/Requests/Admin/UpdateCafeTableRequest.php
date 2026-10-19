<?php

namespace App\Http\Requests\Admin;

use Illuminate\Validation\Rule;

class UpdateCafeTableRequest extends StoreCafeTableRequest
{
    public function rules(): array
    {
        $table = $this->route('cafe_table');
        $rules = parent::rules();
        $rules['table_code'] = ['required', 'string', 'max:20', Rule::unique('cafe_tables', 'table_code')->ignore($table->getKey())];
        $rules['area_id'] = ['required', 'integer', Rule::exists('areas', 'id')->whereNull('deleted_at')
            ->where(fn ($query) => $query->where(fn ($query) => $query->where('status', 'active')->orWhere('id', $table->area_id)))];

        return $rules;
    }

    public function messages(): array
    {
        return array_merge(parent::messages(), ['area_id.exists' => 'Chọn khu vực đang hoạt động, hoặc giữ khu vực hiện tại chưa xóa.']);
    }
}
