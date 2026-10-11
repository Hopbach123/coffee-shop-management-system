<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreToppingRequest;
use App\Http\Requests\Admin\UpdateToppingRequest;
use App\Models\Topping;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ToppingController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate(['search' => ['nullable', 'string', 'max:255']]);
        $search = $filters['search'] ?? '';

        $toppings = Topping::query()
            ->when($search !== '', fn ($query) => $query->where('name', 'like', '%'.$search.'%'))
            ->orderBy('id')
            ->paginate(15)
            ->withQueryString();

        return view('admin.toppings.index', compact('toppings', 'search'));
    }

    public function create(): View
    {
        return view('admin.toppings.create');
    }

    public function store(StoreToppingRequest $request): RedirectResponse
    {
        $topping = new Topping;
        $topping->fill($request->safe()->except('status'));
        $topping->status = $request->validated('status');
        $topping->save();

        return redirect()->route('admin.toppings.index')->with('success', 'Đã tạo topping.');
    }

    public function edit(Topping $topping): View
    {
        return view('admin.toppings.edit', compact('topping'));
    }

    public function update(UpdateToppingRequest $request, Topping $topping): RedirectResponse
    {
        $topping->fill($request->safe()->except('status'));
        $topping->status = $request->validated('status');
        $topping->save();

        return redirect()->route('admin.toppings.index')->with('success', 'Đã cập nhật topping.');
    }

    public function toggleStatus(Topping $topping): RedirectResponse
    {
        $topping->status = $topping->status === 'available' ? 'unavailable' : 'available';
        $topping->save();

        return redirect()->route('admin.toppings.index')->with('success', 'Đã đổi trạng thái topping.');
    }

    public function destroy(Topping $topping): RedirectResponse
    {
        $topping->delete();

        return redirect()->route('admin.toppings.index')->with('success', 'Đã xóa topping.');
    }
}
