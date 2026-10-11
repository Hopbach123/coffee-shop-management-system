<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use Illuminate\View\View;

class MenuController extends Controller
{
    public function index(): View
    {
        $categories = Category::query()
            ->where('status', 'active')
            ->with(['products' => fn ($query) => $query
                ->orderBy('id')
                ->with(['productVariants' => fn ($query) => $query->orderBy('id')])])
            ->orderBy('display_order')
            ->orderBy('id')
            ->get();
        $hasProducts = $categories->contains(fn (Category $category) => $category->products->isNotEmpty());

        return view('public.menu.index', compact('categories', 'hasProducts'));
    }

    public function show(Product $product): View
    {
        $category = Category::query()->whereKey($product->category_id)->where('status', 'active')->firstOrFail();
        $product->setRelation('category', $category);
        $product->load(['productVariants' => fn ($query) => $query->orderBy('id')]);

        return view('public.menu.show', compact('product'));
    }
}
