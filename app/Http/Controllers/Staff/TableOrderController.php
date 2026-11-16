<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Http\Requests\Order\StoreOrderItemRequest;
use App\Models\Category;
use App\Models\ProductVariant;
use App\Services\TableOrderContext;
use DomainException;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TableOrderController extends Controller
{
    public function index(Request $request, int $table, int $order, TableOrderContext $context)
    {
        abort_unless(in_array($request->user('web')?->role, ['admin', 'staff'], true)
            && $request->user('web')?->status === 'active', 403);
        try {
            [$table, $order] = $context->read($table, $order);
            $snapshot = $context->summary($order);
            if ($request->expectsJson()) {
                return response()->json($snapshot);
            }
            $filters = $request->validate(['search' => ['nullable', 'string', 'max:100'], 'category_id' => ['nullable', 'integer', 'exists:categories,id']]);
            $variants = ProductVariant::query()->where('status', 'available')
                ->whereHas('product', fn ($query) => $query->where('status', 'available')->whereNull('deleted_at')
                    ->when($filters['category_id'] ?? null, fn ($query, $id) => $query->where('category_id', $id)))
                ->when($filters['search'] ?? null, fn ($query, $search) => $query->where(fn ($query) => $query
                    ->where('sku', 'like', '%'.$search.'%')->orWhereHas('product', fn ($query) => $query->where('name', 'like', '%'.$search.'%'))))
                ->with('product.category')->orderBy('product_id')->orderBy('id')->paginate(12)->withQueryString();
            $categories = Category::query()->orderBy('name')->orderBy('id')->get(['id', 'name']);

            return view('staff.orders.table-menu', compact('table', 'order', 'snapshot', 'variants', 'categories', 'filters'));
        } catch (QueryException $exception) {
            Log::warning('Table menu read failed.', ['sql_state' => $exception->errorInfo[0] ?? null]);
            abort(503, 'Không thể tải dữ liệu gọi món. Vui lòng thử lại sau.');
        }
    }

    public function store(StoreOrderItemRequest $request, int $table, int $order, TableOrderContext $context)
    {
        try {
            return response()->json($context->save($table, $order, $request->safe()->only(['variant_id', 'quantity', 'note']), $request->user('web')), 201);
        } catch (DomainException $exception) {
            return response()->json(['message' => 'Không thể lưu món với dữ liệu hiện tại.'], 422);
        } catch (QueryException $exception) {
            Log::warning('Table menu save failed.', ['sql_state' => $exception->errorInfo[0] ?? null]);

            return response()->json(['message' => 'Không thể xác nhận kết quả lưu. Hãy kiểm tra đơn trước khi gửi lại.'], 503);
        }
    }
}
