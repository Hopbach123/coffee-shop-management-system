<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'Vui lòng nhập email.',
            'email.email' => 'Vui lòng nhập email hợp lệ.',
            'email.string' => 'Vui lòng nhập email hợp lệ.',
            'password.required' => 'Vui lòng nhập mật khẩu.',
            'password.string' => 'Vui lòng nhập mật khẩu hợp lệ.',
        ];
    }
}
