<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\CafeTable;
use App\Models\Order;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class TableCheckoutController extends Controller
{
    public function show(Request $request, int $table, int $order): View
    {
        abort_unless(in_array($request->user('web')?->role, ['admin', 'staff'], true)
            && $request->user('web')?->status === 'active', 403);
        $tableId = $table;
        $orderId = $order;
        $table = $order = $payments = null;
        try {
            $table = CafeTable::query()->with('area:id,name,status,deleted_at')->findOrFail($tableId);
            $order = Order::query()->where('table_id', $tableId)->where('order_type', 'dine_in')
                ->with(['orderItems' => fn ($query) => $query->with('orderItemToppings')->orderBy('id')])->findOrFail($orderId);
            $payments = $order->payments()->orderByDesc('id')->paginate(10, [
                'id', 'order_id', 'payment_code', 'payment_method', 'amount', 'status', 'paid_at',
            ], 'payments_page');
        } catch (QueryException $exception) {
            Log::warning('Table checkout summary unavailable.', ['sql_state' => $exception->errorInfo[0] ?? null]);
            $table = $order = $payments = null;
        }

        return view('staff.orders.checkout-summary', compact('table', 'order', 'payments', 'tableId', 'orderId'))
            ->with('unavailable', $order === null);
    }

    public function print(Request $request, int $table, int $order): View
    {
        abort_unless(in_array($request->user('web')?->role, ['admin', 'staff'], true)
            && $request->user('web')?->status === 'active', 403);
        $tableId = $table;
        $orderId = $order;
        $table = $order = null;
        try {
            $table = CafeTable::query()->with('area:id,name,status,deleted_at')->findOrFail($tableId);
            $order = Order::query()->where('table_id', $tableId)->where('order_type', 'dine_in')
                ->with(['orderItems' => fn ($query) => $query->with('orderItemToppings')->orderBy('id')])->findOrFail($orderId);
        } catch (QueryException $exception) {
            Log::warning('Table estimate print unavailable.', ['sql_state' => $exception->errorInfo[0] ?? null]);
            $table = $order = null;
        }

        return view('staff.orders.estimate-print', compact('table', 'order', 'tableId', 'orderId'));
    }
}
