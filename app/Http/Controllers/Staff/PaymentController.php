<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Http\Requests\Payment\ApplyOrderPromotionRequest;
use App\Http\Requests\Payment\RecordPaymentRequest;
use App\Models\Order;
use App\Services\PaymentService;
use App\Services\PromotionService;
use DomainException;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PaymentController extends Controller
{
    public function show(Request $request, Order $order): View
    {
        $actor = $request->user('web');
        abort_unless($actor !== null && $actor->status === 'active'
            && in_array($actor->role, ['admin', 'staff'], true), 403);

        $order->load(['customerRequest', 'orderPromotion', 'orderItems.orderItemToppings', 'payments' => fn ($query) => $query->with('creator')->orderByDesc('id')]);
        $customerRequest = $order->customerRequest;
        $isWebTransfer = $customerRequest?->payment_option === 'bank_transfer_prepaid';
        $canPay = $order->status === 'pending' && ! $order->payments->contains('status', 'paid')
            && ($isWebTransfer
                ? ($order->orderItems->isNotEmpty() && $order->orderItems->every(fn ($item) => $item->status === 'pending'))
                : ($customerRequest === null && $order->orderItems->contains('status', 'served')
                    && $order->orderItems->every(fn ($item) => in_array($item->status, ['served', 'cancelled'], true))))
            && $order->total_amount !== '0.00';

        $canApplyPromotion = $order->status === 'pending' && $customerRequest === null
            && $order->orderPromotion === null && ! $order->payments->contains('status', 'paid');

        return view('staff.payments.show', compact('order', 'canPay', 'customerRequest', 'isWebTransfer', 'canApplyPromotion'));
    }

    public function applyPromotion(ApplyOrderPromotionRequest $request, Order $order, PromotionService $service): RedirectResponse
    {
        try {
            $service->applyToOrder($order, $request->validated('code'));
        } catch (DomainException $exception) {
            throw ValidationException::withMessages(['code' => $exception->getMessage()]);
        }

        return redirect()->route('staff.orders.payments.show', $order)
            ->with('success', 'Đã áp dụng khuyến mãi. Vui lòng kiểm tra tổng tiền trước khi thu.');
    }

    public function store(RecordPaymentRequest $request, Order $order, PaymentService $service): RedirectResponse
    {
        if ($request->has('operation_key')) {
            return app(OrderController::class)->storePayment($request, $order, app(\App\Services\OrderService::class));
        }

        try {
            $customerRequest = $order->customerRequest;
            if ($customerRequest?->payment_option === 'cod') {
                throw ValidationException::withMessages(['payment' => 'Thu COD tại bước xác nhận giao thành công trên yêu cầu web.']);
            }
            if ($customerRequest?->payment_option === 'bank_transfer_prepaid') {
                $service->confirmCustomerBankTransfer($order, $request->validated(), $request->user('web'));
            } else {
                $service->recordPaid($order, $request->validated(), $request->user('web'));
            }
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (DomainException $exception) {
            throw ValidationException::withMessages(['payment' => $exception->getMessage()]);
        } catch (QueryException $exception) {
            report($exception);

            return back()->withErrors(['payment' => 'Chưa hoàn tất ghi nhận thanh toán. Nếu đã nhận tiền, kiểm tra lịch sử đơn và giao dịch thực tế trước khi thử lại; không thu tiền lần hai.'])->withInput();
        }

        return redirect()->route('staff.orders.payments.show', $order)->with('success', 'Đã ghi nhận đủ tiền. Tiến độ chuẩn bị/giao món được theo dõi riêng trên đơn hàng.');
    }
}
