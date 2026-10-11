<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreProductRequest;
use App\Http\Requests\Admin\UpdateProductRequest;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use RuntimeException;
use Throwable;

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
        $product->fill($request->safe()->except(['status', 'image']));
        $product->slug = Product::uniqueSlugForName($product->name);
        $product->status = $request->validated('status');
        $newImagePath = $this->storeImage($request);
        $product->image = $newImagePath;

        try {
            if (! $product->save()) {
                throw new RuntimeException('Product could not be created.');
            }
        } catch (Throwable $exception) {
            if ($newImagePath !== null) {
                Storage::disk('public')->delete($newImagePath);
            }
            throw $exception;
        }

        return redirect()->route('admin.products.index')->with('success', 'Đã tạo sản phẩm.');
    }

    public function edit(Product $product): View
    {
        $product->load('category');
        $categories = Category::query()->orderBy('name')->orderBy('id')->get(['id', 'name']);
        $imageUrl = $product->imageUrl();

        return view('admin.products.edit', compact('product', 'categories', 'imageUrl'));
    }

    public function update(UpdateProductRequest $request, Product $product): RedirectResponse
    {
        $oldImagePath = $product->image;
        $newImagePath = $this->storeImage($request);
        $product->fill($request->safe()->except(['status', 'image']));
        $product->status = $request->validated('status');
        if ($newImagePath !== null) {
            $product->image = $newImagePath;
        }

        try {
            if (! $product->save()) {
                throw new RuntimeException('Product could not be updated.');
            }
        } catch (Throwable $exception) {
            if ($newImagePath !== null) {
                Storage::disk('public')->delete($newImagePath);
            }
            throw $exception;
        }

        if ($newImagePath !== null && Product::isManagedImagePath($oldImagePath)) {
            Storage::disk('public')->delete($oldImagePath);
        }

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

    private function storeImage(StoreProductRequest $request): ?string
    {
        if (! $request->hasFile('image')) {
            return null;
        }

        $path = $request->file('image')->store('products', 'public');
        if ($path === false) {
            throw new RuntimeException('Product image could not be stored.');
        }

        return $path;
    }
}
