<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCategoryRequest;
use App\Http\Requests\Admin\UpdateCategoryRequest;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate(['search' => ['nullable', 'string', 'max:255']]);
        $search = $filters['search'] ?? '';

        $categories = Category::query()
            ->when($search !== '', fn ($query) => $query->where('name', 'like', '%'.$search.'%'))
            ->orderBy('display_order')
            ->orderBy('id')
            ->paginate(15)
            ->withQueryString();

        return view('admin.categories.index', compact('categories', 'search'));
    }

    public function create(): View
    {
        return view('admin.categories.create');
    }

    public function store(StoreCategoryRequest $request): RedirectResponse
    {
        $category = new Category;
        $category->fill($request->safe()->except('status'));
        $category->status = $request->validated('status');
        $category->save();

        return redirect()->route('admin.categories.index')->with('success', 'Đã tạo danh mục.');
    }

    public function edit(Category $category): View
    {
        return view('admin.categories.edit', compact('category'));
    }

    public function update(UpdateCategoryRequest $request, Category $category): RedirectResponse
    {
        $category->fill($request->safe()->except('status'));
        $category->status = $request->validated('status');
        $category->save();

        return redirect()->route('admin.categories.index')->with('success', 'Đã cập nhật danh mục.');
    }

    public function toggleStatus(Category $category): RedirectResponse
    {
        $category->status = $category->status === 'active' ? 'inactive' : 'active';
        $category->save();

        return redirect()->route('admin.categories.index')->with('success', 'Đã đổi trạng thái danh mục.');
    }

    public function destroy(Category $category): RedirectResponse
    {
        $category->delete();

        return redirect()->route('admin.categories.index')->with('success', 'Đã xóa danh mục.');
    }
}
