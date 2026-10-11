<?php

namespace App\Http\Requests\Admin;

use Illuminate\Validation\Rule;

class UpdatePromotionRequest extends StorePromotionRequest
{
    public function rules(): array
    {
        $rules = parent::rules();
        $rules['code'] = ['required', 'string', 'max:50', Rule::unique('promotions', 'code')->ignore($this->route('promotion'))];

        return $rules;
    }
}
