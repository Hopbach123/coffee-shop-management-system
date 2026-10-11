<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        // The existing Admin route group enforces auth:web and role:admin.
        return true;
    }

    public function rules(): array
    {
        return [
            'category_id' => ['required', 'integer', Rule::exists('categories', 'id')->whereNull('deleted_at')],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'extensions:jpg,jpeg,png,webp', 'max:5120'],
            'status' => ['required', Rule::in(['available', 'unavailable'])],
            'is_featured' => ['required', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'category_id.required' => 'Vui lòng chọn danh mục.',
            'category_id.exists' => 'Danh mục đã chọn không còn tồn tại.',
            'name.required' => 'Vui lòng nhập tên sản phẩm.',
            'name.max' => 'Tên sản phẩm không được vượt quá 255 ký tự.',
            'description.string' => 'Mô tả phải là chuỗi ký tự.',
            'image.image' => 'Vui lòng chọn tệp ảnh hợp lệ.',
            'image.mimes' => 'Ảnh phải là JPG, PNG hoặc WebP.',
            'image.extensions' => 'Ảnh phải có đuôi JPG, PNG hoặc WebP.',
            'image.max' => 'Ảnh không được vượt quá 5 MB.',
            'status.required' => 'Vui lòng chọn trạng thái.',
            'status.in' => 'Trạng thái phải là available hoặc unavailable.',
            'is_featured.required' => 'Vui lòng chọn trạng thái nổi bật.',
            'is_featured.boolean' => 'Giá trị nổi bật không hợp lệ.',
        ];
    }
}
