<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreProductVariantRequest;
use App\Http\Requests\Admin\UpdateProductVariantRequest;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductVariantController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate(['search' => ['nullable', 'string', 'max:255']]);
        $search = $filters['search'] ?? '';

        $variants = ProductVariant::query()
            ->with('product')
            ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search) {
                $query->where('name', 'like', '%'.$search.'%')
                    ->orWhere('sku', 'like', '%'.$search.'%');
            }))
            ->orderBy('id')
            ->paginate(15)
            ->withQueryString();

        return view('admin.product-variants.index', compact('variants', 'search'));
    }

    public function create(): View
    {
        $products = Product::query()->orderBy('name')->orderBy('id')->get(['id', 'name']);

        return view('admin.product-variants.create', compact('products'));
    }

    public function store(StoreProductVariantRequest $request): RedirectResponse
    {
        $variant = new ProductVariant;
        $variant->fill($request->safe()->except('status'));
        $variant->status = $request->validated('status');
        $variant->save();

        return redirect()->route('admin.product-variants.index')->with('success', 'Đã tạo biến thể.');
    }

    public function edit(ProductVariant $productVariant): View
    {
        $productVariant->load('product');
        $products = Product::query()->orderBy('name')->orderBy('id')->get(['id', 'name']);

        return view('admin.product-variants.edit', compact('productVariant', 'products'));
    }

    public function update(UpdateProductVariantRequest $request, ProductVariant $productVariant): RedirectResponse
    {
        $productVariant->fill($request->safe()->except('status'));
        $productVariant->status = $request->validated('status');
        $productVariant->save();

        return redirect()->route('admin.product-variants.index')->with('success', 'Đã cập nhật biến thể.');
    }

    public function toggleStatus(ProductVariant $productVariant): RedirectResponse
    {
        $productVariant->status = $productVariant->status === 'available' ? 'unavailable' : 'available';
        $productVariant->save();

        return redirect()->route('admin.product-variants.index')->with('success', 'Đã đổi trạng thái biến thể.');
    }

    public function destroy(ProductVariant $productVariant): RedirectResponse
    {
        $productVariant->delete();

        return redirect()->route('admin.product-variants.index')->with('success', 'Đã xóa biến thể.');
    }
}
