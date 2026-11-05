<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ingredient;
use Illuminate\View\View;

class InventoryOverviewController extends Controller
{
    public function history(Ingredient $ingredient): View
    {
        $movements = $ingredient->inventoryTransactions()->with('creator')->orderByDesc('id')->paginate(15);

        return view('admin.ingredients.history', compact('ingredient', 'movements'));
    }

}
