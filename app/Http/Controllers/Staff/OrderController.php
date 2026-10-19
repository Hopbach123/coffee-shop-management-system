<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Http\Requests\Order\StoreOrderRequest;
use App\Models\CafeTable;
use App\Models\Customer;
use App\Models\Order;
use App\Services\OrderService;
use DomainException;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function index(Request $request): View
    {
        $this->assertActiveStaff($request);

        $orders = Order::query()
            ->with(['cafeTable:id,table_code,table_name', 'customer:id,name,phone', 'creator:id,name'])
            ->orderByDesc('ordered_at')
            ->orderByDesc('id')
            ->paginate(15);

        return view('staff.orders.index', compact('orders'));
    }

    public function create(Request $request): View
    {
        $this->assertActiveStaff($request);

        $customers = Customer::query()->where('status', 'active')->orderBy('name')->orderBy('id')->get(['id', 'name', 'phone']);
        $tables = CafeTable::query()
            ->where('status', 'available')
            ->whereHas('area', fn ($query) => $query->where('status', 'active')->whereNull('deleted_at'))
            ->with('area:id,name')
            ->orderBy('table_code')
            ->get(['id', 'area_id', 'table_code', 'table_name', 'capacity']);

        return view('staff.orders.create', compact('customers', 'tables'));
    }

    public function store(StoreOrderRequest $request, OrderService $service): RedirectResponse
    {
        try {
            $order = $service->create($request->safe()->only(['order_type', 'table_id', 'customer_id', 'note']), $request->user('web'));
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (DomainException $exception) {
            throw ValidationException::withMessages(['order' => $exception->getMessage()]);
        } catch (QueryException $exception) {
            report($exception);

            return back()->withErrors(['order' => 'Không thể tạo đơn hàng. Vui lòng thử lại.'])->withInput();
        }

        return redirect()->route('staff.orders.show', $order)->with('success', 'Đã tạo đơn hàng.');
    }

    public function show(Request $request, Order $order): View
    {
        $this->assertActiveStaff($request);
        $order->load(['cafeTable:id,table_code,table_name', 'customer:id,name,phone', 'creator:id,name']);

        return view('staff.orders.show', compact('order'));
    }

    private function assertActiveStaff(Request $request): void
    {
        abort_unless($request->user('web')?->role === 'staff' && $request->user('web')?->status === 'active', 403);
    }
}
