<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreProductRequest;
use App\Http\Requests\Admin\UpdateProductRequest;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate(['search' => ['nullable', 'string', 'max:255']]);
        $search = $filters['search'] ?? '';

        $products = Product::query()
            ->with('category')
            ->when($search !== '', fn ($query) => $query->where('name', 'like', '%'.$search.'%'))
            ->orderBy('id')
            ->paginate(15)
            ->withQueryString();

        return view('admin.products.index', compact('products', 'search'));
    }

    public function create(): View
    {
        $categories = Category::query()->orderBy('name')->orderBy('id')->get(['id', 'name']);

        return view('admin.products.create', compact('categories'));
    }

    public function store(StoreProductRequest $request): RedirectResponse
    {
        $product = new Product;
        $product->fill($request->safe()->except('status'));
        $product->slug = Product::uniqueSlugForName($product->name);
        $product->status = $request->validated('status');
        $product->save();

        return redirect()->route('admin.products.index')->with('success', 'Đã tạo sản phẩm.');
    }

    public function edit(Product $product): View
    {
        $product->load('category');
        $categories = Category::query()->orderBy('name')->orderBy('id')->get(['id', 'name']);

        return view('admin.products.edit', compact('product', 'categories'));
    }

    public function update(UpdateProductRequest $request, Product $product): RedirectResponse
    {
        $product->fill($request->safe()->except('status'));
        $product->status = $request->validated('status');
        $product->save();

        return redirect()->route('admin.products.index')->with('success', 'Đã cập nhật sản phẩm.');
    }

    public function toggleStatus(Product $product): RedirectResponse
    {
        $product->status = $product->status === 'available' ? 'unavailable' : 'available';
        $product->save();

        return redirect()->route('admin.products.index')->with('success', 'Đã đổi trạng thái sản phẩm.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        $product->delete();

        return redirect()->route('admin.products.index')->with('success', 'Đã xóa sản phẩm.');
    }
}
