<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Access is enforced by the existing Admin route group.
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['nullable', 'required_without_all:phone,email', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20', 'regex:/\A(?=.*[0-9])[0-9+() -]+\z/', Rule::unique('customers', 'phone')],
            'email' => ['nullable', 'string', 'email', 'max:255'],
            'birthday' => ['nullable', 'date_format:Y-m-d'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required_without_all' => 'Vui lòng nhập ít nhất họ tên, số điện thoại hoặc email.',
            'name.string' => 'Họ tên phải là chuỗi ký tự.',
            'name.max' => 'Họ tên không được vượt quá 255 ký tự.',
            'phone.string' => 'Số điện thoại phải là chuỗi ký tự.',
            'phone.max' => 'Số điện thoại không được vượt quá 20 ký tự.',
            'phone.regex' => 'Số điện thoại chỉ gồm chữ số, dấu +, khoảng trắng, dấu ngoặc và dấu -.',
            'phone.unique' => 'Số điện thoại này đã được sử dụng.',
            'email.string' => 'Email phải là chuỗi ký tự.',
            'email.email' => 'Vui lòng nhập email hợp lệ.',
            'email.max' => 'Email không được vượt quá 255 ký tự.',
            'birthday.date_format' => 'Ngày sinh phải là ngày hợp lệ theo định dạng YYYY-MM-DD.',
        ];
    }
}
