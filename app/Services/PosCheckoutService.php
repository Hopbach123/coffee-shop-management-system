<?php

namespace App\Services;

use App\Models\Order;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PosCheckoutService
{
    public function __construct(
        private readonly CustomerCartService $cart,
        private readonly OrderService $orders,
        private readonly PaymentService $payments,
    ) {}

    public function review(array $draft): array
    {
        $quote = $this->cart->quote($draft);
        $quote['fingerprint'] = hash('sha256', json_encode([$draft, $quote], JSON_THROW_ON_ERROR));

        return $quote;
    }

    public function checkout(array $attributes, array $draft, string $key, string $quoteHash, User $actor): Order
    {
        if (strlen($key) !== 64 || ! ctype_xdigit($key) || $draft === []) {
            throw ValidationException::withMessages(['cart' => 'Phiên kiểm tra đơn không hợp lệ. Vui lòng kiểm tra giỏ hàng.']);
        }
        $keyHash = hash('sha256', $key);
        $payloadHash = hash('sha256', json_encode([
            $draft, $quoteHash, $attributes['order_type'], $attributes['table_id'] ?? null,
            $attributes['note'] ?? null, $attributes['payment_method'],
            trim($attributes['transaction_code'] ?? ''),
        ], JSON_THROW_ON_ERROR));

        try {
            return DB::transaction(function () use ($attributes, $draft, $keyHash, $payloadHash, $quoteHash, $actor): Order {
                $existing = Order::query()->where('pos_checkout_key_hash', $keyHash)->lockForUpdate()->first();
                if ($existing !== null) {
                    return $this->existingResult($existing, $payloadHash, $actor);
                }

                $order = $this->orders->create($attributes, $actor);
                $order->forceFill(['pos_checkout_key_hash' => $keyHash, 'pos_checkout_payload_hash' => $payloadHash])->saveOrFail();
                $quote = $this->cart->lockAndQuote($draft);
                $quote['fingerprint'] = hash('sha256', json_encode([$draft, $quote], JSON_THROW_ON_ERROR));
                if ($quote['subtotal'] === null || ! hash_equals($quoteHash, $quote['fingerprint'])) {
                    throw ValidationException::withMessages(['cart' => 'Giá hoặc tình trạng món đã thay đổi. Hãy kiểm tra tổng mới và đối soát tiền đã nhận trước khi xác nhận lại.']);
                }

                foreach ($draft as $line) {
                    $item = $this->orders->addItem($order, $line, $actor);
                    foreach ($line['toppings'] as $id => $quantity) {
                        $this->orders->addItemTopping($order, $item, ['topping_id' => $id, 'quantity' => $quantity], $actor);
                    }
                }
                if ($order->fresh()->total_amount !== $quote['subtotal']) {
                    throw ValidationException::withMessages(['cart' => 'Tổng đơn không khớp báo giá. Chưa hoàn tất ghi nhận.']);
                }

                $this->payments->recordPosPrepaymentAndStartPreparing($order, $attributes, $actor);

                return $order->fresh();
            }, 3);
        } catch (UniqueConstraintViolationException $exception) {
            if (! str_contains($exception->getMessage(), 'pos_checkout_key_hash')) {
                throw $exception;
            }

            // A concurrent checkout with this key committed first. Read that result.
            $existing = Order::query()->where('pos_checkout_key_hash', $keyHash)->firstOrFail();

            return $this->existingResult($existing, $payloadHash, $actor);
        }
    }

    private function existingResult(Order $order, string $payloadHash, User $actor): Order
    {
        $currentActor = User::query()->find($actor->getKey());
        if ($currentActor === null || $currentActor->status !== 'active'
            || ! in_array($currentActor->role, ['admin', 'staff'], true)
            || $order->user_id !== $currentActor->id
            || ! hash_equals($order->pos_checkout_payload_hash ?? '', $payloadHash)) {
            throw ValidationException::withMessages(['cart' => 'Khóa checkout đã được dùng cho nội dung hoặc người thao tác khác.']);
        }

        return $order;
    }
}
