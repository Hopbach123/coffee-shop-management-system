<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreStockImportRequest;
use App\Models\Ingredient;
use App\Services\InventoryService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class StockImportController extends Controller
{
    public function create(Request $request, Ingredient $ingredient): View
    {
        abort_unless($ingredient->status === 'active', 422, 'Nguyên liệu đã ngừng dùng. Hãy kích hoạt trước khi nhập kho.');
        $operationToken = Crypt::encryptString(json_encode([
            'key' => (string) random_int(1, 999999999999999999),
            'ingredient' => $ingredient->id,
            'actor' => $request->user('web')->id,
            'unit' => $ingredient->unit,
        ], JSON_THROW_ON_ERROR));

        return view('admin.ingredients.import', compact('ingredient', 'operationToken'));
    }

    public function store(StoreStockImportRequest $request, Ingredient $ingredient, InventoryService $inventory): RedirectResponse
    {
        $operation = $request->operation();
        try {
            $inventory->recordImport($ingredient, $request->user('web'), $request->validated('quantity'),
                $request->validated('note'), $operation['key'], $operation['unit']);
        } catch (DomainException $exception) {
            throw ValidationException::withMessages(['quantity' => $exception->getMessage()]);
        }

        return redirect()->route('admin.ingredients.history', $ingredient)
            ->with('success', 'Phiếu nhập đã được ghi nhận. Gửi lại cùng phiếu không cộng tồn lần hai.');
    }
}
