<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Http\Requests\Order\StoreOrderRequest;
use App\Http\Requests\Order\StoreOrderItemRequest;
use App\Http\Requests\Order\StoreOrderItemToppingRequest;
use App\Http\Requests\Order\UpdateOrderItemQuantityRequest;
use App\Http\Requests\Order\UpdateOrderItemStatusRequest;
use App\Http\Requests\Order\UpdateOrderItemToppingQuantityRequest;
use App\Models\CafeTable;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderItemTopping;
use App\Models\ProductVariant;
use App\Models\Topping;
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
        $order->load([
            'cafeTable:id,table_code,table_name',
            'customer:id,name,phone',
            'creator:id,name',
            'orderItems' => fn ($query) => $query->with('orderItemToppings')->orderBy('id'),
        ]);

        $variants = ProductVariant::query()
            ->where('status', 'available')
            ->whereHas('product', fn ($query) => $query->where('status', 'available'))
            ->with('product:id,name')
            ->orderBy('product_id')
            ->orderBy('id')
            ->get(['id', 'product_id', 'name', 'price']);

        $toppings = Topping::query()
            ->where('status', 'available')
            ->orderBy('name')
            ->orderBy('id')
            ->get(['id', 'name', 'price']);

        $canCancel = $order->status === 'pending'
            && $order->orderItems->every(fn (OrderItem $item): bool => $item->status === 'pending');

        return view('staff.orders.show', compact('order', 'variants', 'toppings', 'canCancel'));
    }

    public function cancel(Request $request, Order $order, OrderService $service): RedirectResponse
    {
        $this->assertActiveStaff($request);

        try {
            $service->cancel($order, $request->user('web'));
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (DomainException $exception) {
            throw ValidationException::withMessages(['order' => $exception->getMessage()]);
        } catch (QueryException $exception) {
            report($exception);

            return back()->withErrors(['order' => 'Không thể hủy đơn hàng. Vui lòng thử lại.']);
        }

        return redirect()->route('staff.orders.show', $order)->with('success', 'Đã hủy đơn hàng.');
    }

    public function storeItem(StoreOrderItemRequest $request, Order $order, OrderService $service): RedirectResponse
    {
        try {
            $service->addItem($order, $request->safe()->only(['variant_id', 'quantity', 'note']), $request->user('web'));
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (DomainException $exception) {
            throw ValidationException::withMessages(['order' => $exception->getMessage()]);
        } catch (QueryException $exception) {
            report($exception);

            return back()->withErrors(['order' => 'Không thể thêm món. Vui lòng thử lại.'])->withInput();
        }

        return redirect()->route('staff.orders.show', $order)->with('success', 'Đã thêm món vào đơn hàng.');
    }

    public function updateItem(UpdateOrderItemQuantityRequest $request, Order $order, OrderItem $orderItem, OrderService $service): RedirectResponse
    {
        try {
            $service->updateItemQuantity($order, $orderItem, $request->safe()->only(['quantity']), $request->user('web'));
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (DomainException $exception) {
            throw ValidationException::withMessages(['order' => $exception->getMessage()]);
        } catch (QueryException $exception) {
            report($exception);

            return back()->withErrors(['order' => 'Không thể cập nhật món. Vui lòng thử lại.'])->withInput();
        }

        return redirect()->route('staff.orders.show', $order)->with('success', 'Đã cập nhật số lượng món.');
    }

    public function updateItemStatus(UpdateOrderItemStatusRequest $request, Order $order, OrderItem $orderItem, OrderService $service): RedirectResponse
    {
        try {
            $service->transitionItemStatus($order, $orderItem, $request->safe()->string('status')->toString(), $request->user('web'));
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (DomainException $exception) {
            throw ValidationException::withMessages(['order' => $exception->getMessage()]);
        } catch (QueryException $exception) {
            report($exception);

            return back()->withErrors(['order' => 'Không thể cập nhật trạng thái món. Vui lòng thử lại.']);
        }

        return redirect()->route('staff.orders.show', $order)->with('success', 'Đã cập nhật trạng thái món.');
    }

    public function destroyItem(Request $request, Order $order, OrderItem $orderItem, OrderService $service): RedirectResponse
    {
        $this->assertActiveStaff($request);

        try {
            $service->removeItem($order, $orderItem, $request->user('web'));
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (DomainException $exception) {
            throw ValidationException::withMessages(['order' => $exception->getMessage()]);
        } catch (QueryException $exception) {
            report($exception);

            return back()->withErrors(['order' => 'Không thể xóa món. Vui lòng thử lại.']);
        }

        return redirect()->route('staff.orders.show', $order)->with('success', 'Đã xóa món khỏi đơn hàng.');
    }

    public function storeItemTopping(StoreOrderItemToppingRequest $request, Order $order, OrderItem $orderItem, OrderService $service): RedirectResponse
    {
        try {
            $service->addItemTopping($order, $orderItem, $request->safe()->only(['topping_id', 'quantity']), $request->user('web'));
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (DomainException $exception) {
            throw ValidationException::withMessages(['order' => $exception->getMessage()]);
        } catch (QueryException $exception) {
            report($exception);

            return back()->withErrors(['order' => 'Không thể thêm topping. Vui lòng thử lại.'])->withInput();
        }

        return redirect()->route('staff.orders.show', $order)->with('success', 'Đã thêm topping vào món.');
    }

    public function destroyItemTopping(Request $request, Order $order, OrderItem $orderItem, OrderItemTopping $orderItemTopping, OrderService $service): RedirectResponse
    {
        $this->assertActiveStaff($request);

        try {
            $service->removeItemTopping($order, $orderItem, $orderItemTopping, $request->user('web'));
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (DomainException $exception) {
            throw ValidationException::withMessages(['order' => $exception->getMessage()]);
        } catch (QueryException $exception) {
            report($exception);

            return back()->withErrors(['order' => 'Không thể xóa topping. Vui lòng thử lại.']);
        }

        return redirect()->route('staff.orders.show', $order)->with('success', 'Đã xóa topping khỏi món.');
    }

    public function updateItemTopping(UpdateOrderItemToppingQuantityRequest $request, Order $order, OrderItem $orderItem, OrderItemTopping $orderItemTopping, OrderService $service): RedirectResponse
    {
        try {
            $service->updateItemToppingQuantity($order, $orderItem, $orderItemTopping, $request->safe()->only(['quantity']), $request->user('web'));
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (DomainException $exception) {
            throw ValidationException::withMessages(['order' => $exception->getMessage()]);
        } catch (QueryException $exception) {
            report($exception);

            return back()->withErrors(['order' => 'Không thể cập nhật topping. Vui lòng thử lại.'])->withInput();
        }

        return redirect()->route('staff.orders.show', $order)->with('success', 'Đã cập nhật số lượng topping.');
    }

    private function assertActiveStaff(Request $request): void
    {
        abort_unless($request->user('web')?->role === 'staff' && $request->user('web')?->status === 'active', 403);
    }
}
