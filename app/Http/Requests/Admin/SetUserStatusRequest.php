<?php

namespace App\Http\Requests\Admin;

class SetUserStatusRequest extends StoreUserRequest
{
    public function rules(): array
    {
        return ['status' => parent::rules()['status']];
    }
}
