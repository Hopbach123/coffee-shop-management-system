<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Http\Requests\Payment\RecordPaymentRequest;
use App\Models\Order;
use App\Services\PaymentService;
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

        $order->load(['customerRequest', 'orderItems.orderItemToppings', 'payments' => fn ($query) => $query->with('creator')->orderByDesc('id')]);
        $customerRequest = $order->customerRequest;
        $isWebTransfer = $customerRequest?->payment_option === 'bank_transfer_prepaid';
        $canPay = $order->status === 'pending' && ! $order->payments->contains('status', 'paid')
            && ($isWebTransfer
                ? ($order->orderItems->isNotEmpty() && $order->orderItems->every(fn ($item) => $item->status === 'pending'))
                : ($customerRequest === null && $order->orderItems->contains('status', 'served')
                    && $order->orderItems->every(fn ($item) => in_array($item->status, ['served', 'cancelled'], true))))
            && $order->total_amount !== '0.00';

        return view('staff.payments.show', compact('order', 'canPay', 'customerRequest', 'isWebTransfer'));
    }

    public function store(RecordPaymentRequest $request, Order $order, PaymentService $service): RedirectResponse
    {
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

            return back()->withErrors(['payment' => 'Chưa ghi nhận được thanh toán. Vui lòng kiểm tra và thử lại.'])->withInput();
        }

        return redirect()->route('staff.orders.payments.show', $order)->with('success', 'Đã ghi nhận đủ tiền. Tiến độ chuẩn bị/giao món được theo dõi riêng trên đơn hàng.');
    }
}
