<?php

namespace App\Http\Requests\Admin;

use App\Models\ProductVariant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductVariantRequest extends FormRequest
{
    public function authorize(): bool
    {
        // The Admin route group enforces auth:web and role:admin.
        return true;
    }

    public function rules(): array
    {
        $variant = $this->route('product_variant');
        $nameUnique = Rule::unique('product_variants', 'name')
            ->where('product_id', $this->input('product_id'));
        $skuUnique = Rule::unique('product_variants', 'sku');

        if ($variant instanceof ProductVariant) {
            $nameUnique->ignore($variant);
            $skuUnique->ignore($variant);
        }

        return [
            'product_id' => ['required', 'integer', Rule::exists('products', 'id')->whereNull('deleted_at')],
            'name' => ['required', 'string', 'max:50', $nameUnique],
            'sku' => ['nullable', 'string', 'max:50', $skuUnique],
            'price' => ['required', 'numeric', 'min:0', 'max:9999999999.99', 'decimal:0,2'],
            'status' => ['required', Rule::in(['available', 'unavailable'])],
        ];
    }

    public function messages(): array
    {
        return [
            'product_id.required' => 'Vui lòng chọn sản phẩm.',
            'product_id.exists' => 'Sản phẩm đã chọn không còn tồn tại.',
            'name.required' => 'Vui lòng nhập tên biến thể.',
            'name.max' => 'Tên biến thể không được vượt quá 50 ký tự.',
            'name.unique' => 'Tên biến thể đã tồn tại trong sản phẩm này.',
            'sku.max' => 'SKU không được vượt quá 50 ký tự.',
            'sku.unique' => 'SKU đã được sử dụng.',
            'price.required' => 'Vui lòng nhập giá bán.',
            'price.numeric' => 'Giá bán phải là số.',
            'price.min' => 'Giá bán không được âm.',
            'price.max' => 'Giá bán vượt quá giới hạn cho phép.',
            'price.decimal' => 'Giá bán chỉ được có tối đa hai chữ số thập phân.',
            'status.required' => 'Vui lòng chọn trạng thái.',
            'status.in' => 'Trạng thái phải là available hoặc unavailable.',
        ];
    }
}
