<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Http\Requests\Payment\ConfirmCustomerDeliveryRequest;
use App\Models\CustomerOrderRequest;
use App\Services\CustomerOrderService;
use DomainException;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CustomerOrderRequestController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorizeActor($request);

        return view('staff.customer-orders.index', ['requests' => CustomerOrderRequest::with('order')->latest('id')->paginate(20)]);
    }

    public function show(Request $request, CustomerOrderRequest $customerRequest): View
    {
        $this->authorizeActor($request);
        $customerRequest->load(['items.toppings', 'order.orderItems', 'order.payments']);
        $order = $customerRequest->order;
        $allReady = $order !== null && $order->status === 'pending' && $order->orderItems->isNotEmpty()
            && $order->orderItems->every(fn ($item): bool => $item->status === 'ready');

        return view('staff.customer-orders.show', ['customerRequest' => $customerRequest,
            'canPrepare' => $customerRequest->payment_option === 'cod' && $order?->status === 'pending' && $order->orderItems->contains('status', 'pending'),
            'canDispatch' => $allReady && $customerRequest->dispatched_at === null,
            'canDeliver' => $allReady && $customerRequest->dispatched_at !== null && $customerRequest->delivered_at === null]);
    }

    public function accept(Request $request, CustomerOrderRequest $customerRequest, CustomerOrderService $service): RedirectResponse
    {
        return $this->respond($request, $customerRequest, fn () => $service->accept($customerRequest, $request->user('web')), 'Đã tiếp nhận yêu cầu thành đơn hàng.');
    }

    public function prepare(Request $request, CustomerOrderRequest $customerRequest, CustomerOrderService $service): RedirectResponse
    {
        return $this->respond($request, $customerRequest, fn () => $service->prepareCod($customerRequest, $request->user('web')), 'Đã bắt đầu chuẩn bị đơn COD.');
    }

    public function dispatch(Request $request, CustomerOrderRequest $customerRequest, CustomerOrderService $service): RedirectResponse
    {
        return $this->respond($request, $customerRequest, fn () => $service->dispatch($customerRequest, $request->user('web')), 'Đã ghi nhận bắt đầu giao. Chưa xác nhận khách nhận hoặc thanh toán COD.');
    }

    public function deliver(ConfirmCustomerDeliveryRequest $request, CustomerOrderRequest $customerRequest, CustomerOrderService $service): RedirectResponse
    {
        return $this->respond($request, $customerRequest, fn () => $service->confirmDelivery($customerRequest, $request->validated(), $request->user('web')), 'Đã xác nhận giao thành công và ghi nhận tiền theo phương thức thực nhận.');
    }

    private function respond(Request $request, CustomerOrderRequest $record, callable $action, string $success): RedirectResponse
    {
        $this->authorizeActor($request);
        try {
            $action();
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (DomainException $exception) {
            throw ValidationException::withMessages(['request' => $exception->getMessage()]);
        } catch (QueryException $exception) {
            Log::error('Customer request operation failed.', ['exception_type' => $exception::class]);

            return back()->withInput()->withErrors(['request' => 'Chưa hoàn tất ghi nhận. Nếu đã thu tiền, kiểm tra giao dịch trước khi thử lại; không thu tiền lần hai.']);
        }

        return redirect()->route('staff.customer-orders.show', $record)->with('success', $success);
    }

    private function authorizeActor(Request $request): void
    {
        abort_unless($request->user('web')?->status === 'active'
            && in_array($request->user('web')?->role, ['admin', 'staff'], true), 403);
    }
}
