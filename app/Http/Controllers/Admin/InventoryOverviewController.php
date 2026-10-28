<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ingredient;
use App\Models\ProductVariant;
use Illuminate\View\View;

class InventoryOverviewController extends Controller
{
    public function history(Ingredient $ingredient): View
    {
        $movements = $ingredient->inventoryTransactions()->with('creator')->orderByDesc('id')->paginate(15);

        return view('admin.ingredients.history', compact('ingredient', 'movements'));
    }

    public function recipe(ProductVariant $productVariant): View
    {
        $productVariant->load('product');
        $recipes = $productVariant->productRecipes()->with('ingredient')->orderBy('id')->get();

        return view('admin.product-variants.recipe', compact('productVariant', 'recipes'));
    }
}
