<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\Topping;
use App\Services\CustomerCartService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MenuController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) ($request->validate(['search' => ['nullable', 'string', 'max:100']])['search'] ?? ''));
        $categories = Category::query()
            ->where('status', 'active')
            ->with(['products' => fn ($query) => $query
                ->orderBy('id')
                ->with(['productVariants' => fn ($query) => $query->orderBy('id')])])
            ->orderBy('display_order')
            ->orderBy('id')
            ->get();
        $hasProducts = $categories->contains(fn (Category $category) => $category->products->isNotEmpty());
        $searchCategories = $search !== '' ? $this->searchCategories($search) : collect();

        return view('public.menu.index', compact('categories', 'hasProducts', 'search', 'searchCategories'));
    }

    public function search(Request $request): View
    {
        $query = trim((string) ($request->validate(['q' => ['nullable', 'string', 'max:100']])['q'] ?? ''));
        $categories = $query !== '' ? $this->searchCategories($query) : collect();

        return view('public.menu._search-results', compact('categories', 'query'));
    }

    public function show(Product $product, CustomerCartService $cart): View
    {
        $category = Category::query()->whereKey($product->category_id)->where('status', 'active')->firstOrFail();
        $product->setRelation('category', $category);
        $product->load(['productVariants' => fn ($query) => $query->orderBy('id')]);

        $cartVariants = $product->productVariants->where('status', 'available')->map(fn ($variant) => [
            'id' => $variant->id, 'name' => $variant->name, 'price_label' => $cart->formatPrice($variant->price),
        ]);
        $cartToppings = Topping::query()->where('status', 'available')->orderBy('name')->orderBy('id')->get()
            ->map(fn (Topping $topping) => ['id' => $topping->id, 'name' => $topping->name,
                'price_label' => $cart->formatPrice($topping->price)]);

        return view('public.menu.show', compact('product', 'cartVariants', 'cartToppings'));
    }

    private function searchCategories(string $query): Collection
    {
        $term = '%'.$query.'%';

        return Category::query()
            ->where('status', 'active')
            ->whereHas('products', fn ($products) => $products->where('name', 'like', $term))
            ->with(['products' => fn ($products) => $products
                ->where('name', 'like', $term)
                ->orderBy('id')
                ->with(['productVariants' => fn ($variants) => $variants->orderBy('id')])])
            ->orderBy('display_order')
            ->orderBy('id')
            ->get();
    }
}
