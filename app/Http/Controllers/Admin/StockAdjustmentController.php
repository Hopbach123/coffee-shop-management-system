<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreStockAdjustmentRequest;
use App\Models\Ingredient;
use App\Models\InventoryTransaction;
use App\Services\InventoryService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class StockAdjustmentController extends Controller
{
    public function create(Request $request, Ingredient $ingredient): View
    {
        abort_unless($ingredient->status === 'active', 422, 'Hãy kích hoạt nguyên liệu trước khi điều chỉnh.');
        $operationToken = Crypt::encryptString(json_encode([
            'purpose' => 'stock_adjustment', 'key' => (string) random_int(1, 999999999999999999),
            'ingredient' => $ingredient->id, 'actor' => $request->user('web')->id,
            'unit' => $ingredient->unit, 'stock' => $ingredient->current_stock,
            'ledger' => (int) InventoryTransaction::query()->where('ingredient_id', $ingredient->id)->max('id'),
        ], JSON_THROW_ON_ERROR));

        return view('admin.ingredients.adjustment', compact('ingredient', 'operationToken'));
    }

    public function store(StoreStockAdjustmentRequest $request, Ingredient $ingredient, InventoryService $inventory): RedirectResponse
    {
        try {
            $inventory->recordAdjustment($ingredient, $request->user('web'), $request->validated('mode'),
                $request->validated('quantity'), $request->validated('note'), $request->operation());
        } catch (DomainException $exception) {
            throw ValidationException::withMessages(['quantity' => $exception->getMessage()]);
        }

        return redirect()->route('admin.ingredients.history', $ingredient)->with('success', 'Đã ghi phiếu kho. Gửi lại cùng phiếu không ghi nhận lần hai.');
    }
}
