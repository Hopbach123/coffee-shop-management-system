<?php

namespace App\Http\Requests\Admin;

use Illuminate\Validation\Rule;

class UpdateUserRequest extends StoreUserRequest
{
    public function rules(): array
    {
        $rules = parent::rules();
        unset($rules['password']);
        $rules['email'] = ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->route('user')->getKey())];

        return $rules;
    }
}
