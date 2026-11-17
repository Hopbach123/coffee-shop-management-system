<?php

namespace App\Services;

use App\Models\CustomerOrderRequest;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\User;
use DomainException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PaymentService
{
    public function __construct(
        private readonly OrderService $orders,
        private readonly PromotionService $promotions,
    ) {}

    public function recordPaid(Order $order, array $attributes, User $actor): Payment
    {
        if (! in_array($attributes['payment_method'] ?? null, ['cash', 'bank_transfer'], true)
            || ! in_array($attributes['received_confirmation'] ?? null, [true, 1, '1', 'true', 'yes', 'on'], true)) {
            throw ValidationException::withMessages(['payment' => 'Phải chọn phương thức và xác nhận đã nhận đủ tiền.']);
        }
        $reference = $attributes['transaction_code'] ?? null;
        if ($reference !== null && (! is_string($reference) || mb_strlen(trim($reference)) > 100)) {
            throw ValidationException::withMessages(['transaction_code' => 'Mã giao dịch tối đa 100 ký tự.']);
        }

        for ($attempt = 0; $attempt < 3; $attempt++) {
            try {
                return DB::transaction(function () use ($order, $attributes, $actor, $reference): Payment {
                    $lockedOrder = Order::query()->lockForUpdate()->findOrFail($order->getKey());
                    $currentActor = User::query()->find($actor->getKey());
                    if ($currentActor === null || $currentActor->status !== 'active'
                        || ! in_array($currentActor->role, ['admin', 'staff'], true)) {
                        throw new DomainException('Chỉ Admin hoặc Staff đang hoạt động được ghi nhận thanh toán.');
                    }
                    $items = OrderItem::query()->where('order_id', $lockedOrder->getKey())
                        ->orderBy('id')->lockForUpdate()->get();
                    if ($lockedOrder->status !== 'pending'
                        || ! $items->contains('status', 'served')
                        || $items->contains(fn (OrderItem $item): bool => ! in_array($item->status, ['served', 'cancelled'], true))) {
                        throw ValidationException::withMessages(['order' => 'Đơn phải đang xử lý và mọi món chưa hủy phải được phục vụ trước khi thanh toán.']);
                    }
                    $paidExists = Payment::query()->where('order_id', $lockedOrder->getKey())
                        ->where('status', 'paid')->lockForUpdate()->exists();
                    if ($paidExists) {
                        throw ValidationException::withMessages(['payment' => 'Đơn đã có thanh toán thành công.']);
                    }

                    $this->orders->refreshTotalsForPayment($lockedOrder);
                    $amount = $lockedOrder->total_amount;
                    if (! preg_match('/^\d{1,13}\.\d{2}$/', $amount) || $amount === '0.00') {
                        throw ValidationException::withMessages(['order' => 'Tổng thanh toán phải lớn hơn 0 và đúng giới hạn dữ liệu.']);
                    }

                    $this->promotions->lockPromotionForPayment($lockedOrder);
                    $payment = new Payment;
                    $payment->forceFill([
                        'order_id' => $lockedOrder->getKey(),
                        'payment_code' => 'PAY-'.Str::upper(Str::random(24)),
                        'payment_method' => $attributes['payment_method'],
                        'amount' => $amount,
                        'transaction_code' => $attributes['payment_method'] === 'bank_transfer'
                            ? (trim($reference ?? '') ?: null) : null,
                        'status' => 'paid',
                        'paid_at' => now(),
                        'created_by' => $currentActor->getKey(),
                    ]);
                    $payment->saveOrFail();
                    $this->promotions->consumeUsageAfterPaid($lockedOrder);

                    $this->orders->completePaidOrderIfAllServed($lockedOrder);

                    return $payment;
                }, 3);
            } catch (UniqueConstraintViolationException $exception) {
                // Only payment-code collisions justify regenerating a code.
                if ($attempt === 2 || ! str_contains($exception->getMessage(), 'payment_code')) {
                    throw $exception;
                }
            }
        }

        throw new DomainException('Không thể tạo mã thanh toán duy nhất.');
    }

    /**
     * Called only by the POS checkout transaction after Order and items are created.
     * A failed payment or preparation must also roll back that newly created Order.
     */
    public function recordPosPrepaymentAndStartPreparing(Order $order, array $attributes, User $actor): Payment
    {
        return $this->recordPrepaymentAndStartPreparing($order, $attributes, $actor);
    }

    public function confirmCustomerBankTransfer(Order $order, array $attributes, User $actor): Payment
    {
        return DB::transaction(function () use ($order, $attributes, $actor): Payment {
            $lockedOrder = Order::lockForUpdate()->findOrFail($order->id);
            $request = CustomerOrderRequest::where('order_id', $lockedOrder->id)->lockForUpdate()->first();
            if ($request === null || $request->status !== 'accepted' || $request->payment_option !== 'bank_transfer_prepaid'
                || ($attributes['payment_method'] ?? null) !== 'bank_transfer') {
                throw ValidationException::withMessages(['payment' => 'Chỉ xác nhận chuyển khoản cho đúng yêu cầu web đã tiếp nhận.']);
            }

            return $this->recordPrepaymentAndStartPreparing($lockedOrder, $attributes, $actor);
        }, 3);
    }

    /** COD collection is part of the delivery-confirmation transaction. */
    public function recordCustomerCodReceipt(Order $order, array $attributes, User $actor): Payment
    {
        if (DB::transactionLevel() === 0) {
            throw new DomainException('Thu COD phải nằm trong transaction xác nhận giao hàng.');
        }
        $request = CustomerOrderRequest::where('order_id', $order->id)->lockForUpdate()->first();
        if ($request === null || $request->status !== 'accepted' || $request->payment_option !== 'cod' || $request->delivered_at === null) {
            throw ValidationException::withMessages(['payment' => 'COD chỉ được ghi nhận khi xác nhận khách đã nhận món và trả đủ tiền.']);
        }

        return $this->recordPaid($order, $attributes, $actor);
    }

    private function recordPrepaymentAndStartPreparing(Order $order, array $attributes, User $actor): Payment
    {
        if (DB::transactionLevel() === 0) {
            throw new DomainException('POS phải tạo đơn và thanh toán trong cùng transaction.');
        }
        if (! in_array($attributes['payment_method'] ?? null, ['cash', 'bank_transfer'], true)
            || ! in_array($attributes['received_confirmation'] ?? null, [true, 1, '1', 'true', 'yes', 'on'], true)) {
            throw ValidationException::withMessages(['payment' => 'Phải chọn phương thức và xác nhận đã nhận đủ tiền.']);
        }
        $reference = $attributes['transaction_code'] ?? null;
        if ($reference !== null && (! is_string($reference) || mb_strlen(trim($reference)) > 100)) {
            throw ValidationException::withMessages(['transaction_code' => 'Mã giao dịch tối đa 100 ký tự.']);
        }

        for ($attempt = 0; $attempt < 3; $attempt++) {
            try {
                return DB::transaction(function () use ($order, $attributes, $actor, $reference): Payment {
                    $lockedOrder = Order::query()->lockForUpdate()->findOrFail($order->getKey());
                    $currentActor = User::query()->find($actor->getKey());
                    if ($currentActor === null || $currentActor->status !== 'active'
                        || ! in_array($currentActor->role, ['admin', 'staff'], true)) {
                        throw new DomainException('Chỉ Admin hoặc Staff đang hoạt động được ghi nhận thanh toán.');
                    }
                    $items = OrderItem::query()->where('order_id', $lockedOrder->getKey())
                        ->orderBy('id')->lockForUpdate()->get();
                    if ($lockedOrder->status !== 'pending' || $items->isEmpty()
                        || $items->contains(fn (OrderItem $item): bool => $item->status !== 'pending')) {
                        throw ValidationException::withMessages(['order' => 'POS chỉ thu trước cho đơn đang xử lý với toàn bộ món chờ pha chế.']);
                    }
                    if (Payment::query()->where('order_id', $lockedOrder->getKey())
                        ->where('status', 'paid')->lockForUpdate()->exists()) {
                        throw ValidationException::withMessages(['payment' => 'Đơn đã có thanh toán thành công.']);
                    }

                    $this->orders->refreshTotalsForPayment($lockedOrder);
                    $amount = $lockedOrder->total_amount;
                    if (! preg_match('/^\d{1,13}\.\d{2}$/', $amount) || $amount === '0.00') {
                        throw ValidationException::withMessages(['order' => 'Tổng thanh toán phải lớn hơn 0 và đúng giới hạn dữ liệu.']);
                    }

                    $this->promotions->lockPromotionForPayment($lockedOrder);
                    $payment = new Payment;
                    $payment->forceFill([
                        'order_id' => $lockedOrder->getKey(),
                        'payment_code' => 'PAY-'.Str::upper(Str::random(24)),
                        'payment_method' => $attributes['payment_method'],
                        'amount' => $amount,
                        'transaction_code' => $attributes['payment_method'] === 'bank_transfer'
                            ? (trim($reference ?? '') ?: null) : null,
                        'status' => 'paid',
                        'paid_at' => now(),
                        'created_by' => $currentActor->getKey(),
                    ]);
                    $payment->saveOrFail();
                    $this->promotions->consumeUsageAfterPaid($lockedOrder);

                    foreach ($items as $item) {
                        $this->orders->transitionItemStatus($lockedOrder, $item, 'preparing', $currentActor);
                    }

                    return $payment;
                });
            } catch (UniqueConstraintViolationException $exception) {
                if ($attempt === 2 || ! str_contains($exception->getMessage(), 'payment_code')) {
                    throw $exception;
                }
            }
        }

        throw new DomainException('Không thể tạo mã thanh toán duy nhất.');
    }
}
