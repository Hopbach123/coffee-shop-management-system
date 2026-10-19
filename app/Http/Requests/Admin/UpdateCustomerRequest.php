<?php

namespace App\Http\Requests\Admin;

use Illuminate\Validation\Rule;

class UpdateCustomerRequest extends StoreCustomerRequest
{
    public function rules(): array
    {
        $rules = parent::rules();
        $rules['phone'] = ['nullable', 'string', 'max:20', 'regex:/\A(?=.*[0-9])[0-9+() -]+\z/', Rule::unique('customers', 'phone')->ignore($this->route('customer')->getKey())];

        return $rules;
    }
}
