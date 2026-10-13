<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreIngredientRequest;
use App\Http\Requests\Admin\UpdateIngredientRequest;
use App\Models\Ingredient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class IngredientController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate(['search' => ['nullable', 'string', 'max:255']]);
        $search = $filters['search'] ?? '';

        $ingredients = Ingredient::query()
            ->when($search !== '', fn ($query) => $query->where('name', 'like', '%'.$search.'%'))
            ->orderBy('id')
            ->paginate(15)
            ->withQueryString();

        return view('admin.ingredients.index', compact('ingredients', 'search'));
    }

    public function create(): View
    {
        return view('admin.ingredients.create');
    }

    public function store(StoreIngredientRequest $request): RedirectResponse
    {
        $ingredient = new Ingredient;
        $ingredient->fill($request->safe()->except('status'));
        $ingredient->status = $request->validated('status');
        $ingredient->save();

        return redirect()->route('admin.ingredients.index')->with('success', 'Đã tạo nguyên liệu.');
    }

    public function edit(Ingredient $ingredient): View
    {
        return view('admin.ingredients.edit', compact('ingredient'));
    }

    public function update(UpdateIngredientRequest $request, Ingredient $ingredient): RedirectResponse
    {
        $ingredient->fill($request->safe()->except('status'));
        $ingredient->status = $request->validated('status');
        $ingredient->save();

        return redirect()->route('admin.ingredients.index')->with('success', 'Đã cập nhật nguyên liệu.');
    }

    public function toggleStatus(Ingredient $ingredient): RedirectResponse
    {
        $ingredient->status = $ingredient->status === 'active' ? 'inactive' : 'active';
        $ingredient->save();

        return redirect()->route('admin.ingredients.index')->with('success', 'Đã đổi trạng thái nguyên liệu.');
    }

    public function destroy(Ingredient $ingredient): RedirectResponse
    {
        $ingredient->delete();

        return redirect()->route('admin.ingredients.index')->with('success', 'Đã xóa nguyên liệu.');
    }
}
