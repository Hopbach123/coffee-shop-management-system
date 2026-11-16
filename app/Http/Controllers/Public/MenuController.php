<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MenuController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'category' => ['nullable', 'integer', Rule::exists('categories', 'id')->where('status', 'active')->whereNull('deleted_at')],
            'page' => ['nullable', 'integer', 'min:1', 'max:2147483647'],
        ]);
        $search = trim($filters['search'] ?? '');
        $selectedCategory = $filters['category'] ?? null;
        $categoryChoices = Category::query()->where('status', 'active')
            ->orderBy('display_order')->orderBy('id')->get();
        $categories = $categoryChoices->filter(fn (Category $category) => $selectedCategory === null || (int) $category->id === (int) $selectedCategory);
        $products = Product::query()->whereIn('category_id', $categories->modelKeys())
            ->when($search !== '', fn ($query) => $query->where('name', 'like', '%'.$search.'%'))
            ->with('productVariants')
            ->orderBy(Category::query()->select('display_order')->whereColumn('categories.id', 'products.category_id')->limit(1))
            ->orderBy('category_id')->orderBy('id')
            ->paginate(12)->appends(array_filter(['search' => $search, 'category' => $selectedCategory], fn ($value) => $value !== null && $value !== ''));
        abort_if($products->currentPage() > $products->lastPage(), 404);
        $hasProducts = $products->total() > 0;
        foreach ($categories as $category) {
            $category->setRelation('products', $products->getCollection()->where('category_id', $category->id)->values());
        }
        $searchCategories = $categories->filter(fn (Category $category) => $category->products->isNotEmpty());

        return view('public.menu.index', compact('categories', 'categoryChoices', 'selectedCategory', 'products', 'hasProducts', 'search', 'searchCategories'));
    }

    public function search(Request $request): View
    {
        $request->validate(['q' => ['nullable', 'string', 'max:100']]);
        // Use the same filtering and pagination as the full GET page.
        $request->merge(['search' => $request->query('q', '')]);
        $data = $this->index($request)->getData();
        $query = $data['search'];
        $categories = $data['searchCategories'];
        $products = $data['products'];
        $products->withPath(route('menu.index'));

        return view('public.menu._search-results', compact('categories', 'query', 'products'));
    }

    public function show(Product $product): View
    {
        $category = Category::query()->whereKey($product->category_id)->where('status', 'active')->firstOrFail();
        $product->setRelation('category', $category);
        $product->load(['productVariants' => fn ($query) => $query->orderBy('id')]);

        return view('public.menu.show', compact('product'));
    }
}
