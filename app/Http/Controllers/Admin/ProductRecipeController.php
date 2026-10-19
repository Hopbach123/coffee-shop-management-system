<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreProductRecipeRequest;
use App\Http\Requests\Admin\UpdateProductRecipeRequest;
use App\Models\Ingredient;
use App\Models\ProductRecipe;
use App\Models\ProductVariant;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ProductRecipeController extends Controller
{
    public function index(ProductVariant $productVariant): View
    {
        $productVariant->load('product');
        $recipes = $productVariant->productRecipes()->with('ingredient')->orderBy('id')->get();
        $ingredients = Ingredient::query()->where('status', 'active')
            ->whereNotIn('id', $recipes->pluck('ingredient_id'))
            ->orderBy('name')->orderBy('id')->get(['id', 'name', 'unit']);

        return view('admin.product-recipes.index', compact('productVariant', 'recipes', 'ingredients'));
    }

    public function store(StoreProductRecipeRequest $request, ProductVariant $productVariant): RedirectResponse
    {
        try {
            $productVariant->productRecipes()->create($request->validated());
        } catch (UniqueConstraintViolationException $exception) {
            if (! $productVariant->productRecipes()->where('ingredient_id', $request->validated('ingredient_id'))->exists()) {
                throw $exception;
            }
            throw ValidationException::withMessages(['ingredient_id' => 'Nguyên liệu đã có trong công thức.']);
        }

        return redirect()->route('admin.product-recipes.index', $productVariant)->with('success', 'Đã thêm nguyên liệu vào công thức.');
    }

    public function update(UpdateProductRecipeRequest $request, ProductVariant $productVariant, ProductRecipe $productRecipe): RedirectResponse
    {
        $productRecipe->update($request->validated());

        return redirect()->route('admin.product-recipes.index', $productVariant)->with('success', 'Đã cập nhật định lượng.');
    }

    public function destroy(ProductVariant $productVariant, ProductRecipe $productRecipe): RedirectResponse
    {
        $productRecipe->delete();

        return redirect()->route('admin.product-recipes.index', $productVariant)->with('success', 'Đã bỏ nguyên liệu khỏi công thức.');
    }
}
