<?php

namespace App\Services;

use App\Models\CustomerOrderRequest;
use App\Models\Order;
use App\Models\User;
use DomainException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CustomerOrderService
{
    public const DELIVERY_FEE = '20000.00';

    public function __construct(
        private readonly CustomerCartService $cart,
        private readonly OrderService $orders,
        private readonly PaymentService $payments,
        private readonly PromotionService $promotions,
    ) {}

    public function review(array $draft, bool $lockCatalog = false, ?string $promotionCode = null): array
    {
        $quote = $lockCatalog ? $this->cart->lockAndQuote($draft) : $this->cart->quote($draft);
        $subtotal = $quote['subtotal'];
        $promotion = $subtotal === null || $promotionCode === null || trim($promotionCode) === ''
            ? null
            : $this->promotions->previewForCustomerSubtotal($promotionCode, $subtotal);
        $discount = $promotion['discount'] ?? '0.00';
        $total = $subtotal === null ? null : $this->addFee($subtotal, $discount);
        $quote['delivery_fee'] = self::DELIVERY_FEE;
        $quote['delivery_fee_label'] = '20.000,00 ₫';
        $quote['promotion_code'] = $promotion['code'] ?? null;
        $quote['promotion_discount'] = $discount;
        $quote['promotion_discount_label'] = $this->moneyLabel($discount);
        $quote['total'] = $total;
        $quote['total_label'] = $total === null ? null : $this->moneyLabel($total);
        $quote['fingerprint'] = hash('sha256', json_encode([$draft, $quote], JSON_THROW_ON_ERROR));

        return $quote;
    }

    public function submit(array $attributes, array $draft, string $key, string $token, string $quoteHash, ?string $promotionCode = null): CustomerOrderRequest
    {
        if ($draft === [] || strlen($key) !== 64 || ! ctype_xdigit($key) || strlen($token) !== 64 || ! ctype_xdigit($token)) {
            throw ValidationException::withMessages(['cart' => 'Phiên đặt món không hợp lệ. Vui lòng kiểm tra giỏ hàng.']);
        }
        $contact = array_intersect_key($attributes, array_flip(['customer_name', 'customer_phone', 'customer_address', 'note', 'payment_option']));
        $keyHash = hash('sha256', $key);
        $tokenHash = hash('sha256', $token);
        $payloadHash = hash('sha256', json_encode([$draft, $quoteHash, $contact, $promotionCode], JSON_THROW_ON_ERROR));

        try {
            return DB::transaction(function () use ($draft, $contact, $keyHash, $tokenHash, $payloadHash, $quoteHash, $promotionCode): CustomerOrderRequest {
                $existing = CustomerOrderRequest::where('submission_key_hash', $keyHash)->lockForUpdate()->first();
                if ($existing !== null) {
                    return $this->existingRequest($existing, $tokenHash, $payloadHash);
                }
                $quote = $this->review($draft, true, $promotionCode);
                if ($quote['total'] === null || ! hash_equals($quoteHash, $quote['fingerprint'])) {
                    throw ValidationException::withMessages(['cart' => 'Giá hoặc món đã thay đổi. Vui lòng kiểm tra lại giỏ hàng và tổng tiền trước khi gửi.']);
                }
                $request = CustomerOrderRequest::forceCreate(array_merge($contact, [
                    'reference' => (string) Str::ulid(), 'status' => 'pending',
                    'quoted_subtotal' => $quote['subtotal'], 'promotion_code' => $quote['promotion_code'],
                    'promotion_discount' => $quote['promotion_discount'], 'delivery_fee' => self::DELIVERY_FEE,
                    'quoted_total' => $quote['total'], 'submission_key_hash' => $keyHash,
                    'payload_hash' => $payloadHash, 'access_token_hash' => $tokenHash,
                    'access_expires_at' => now()->addDays(7),
                ]));
                foreach ($quote['items'] as $line) {
                    $item = $request->items()->create([
                        'variant_id' => $line['variant_id'], 'product_name' => $line['name'],
                        'variant_name' => $line['variant_name'], 'quantity' => $line['quantity'],
                        'unit_price' => $line['unit_price'], 'topping_amount' => $line['topping_amount'],
                        'subtotal' => $line['subtotal'], 'note' => $line['note'],
                    ]);
                    foreach ($line['toppings'] as $topping) {
                        $item->toppings()->create(['topping_id' => $topping['id'], 'topping_name' => $topping['name'],
                            'price' => $topping['price'], 'quantity' => $topping['quantity']]);
                    }
                }

                return $request;
            }, 3);
        } catch (UniqueConstraintViolationException $exception) {
            if (! str_contains($exception->getMessage(), 'submission_key')) {
                throw $exception;
            }

            return $this->existingRequest(CustomerOrderRequest::where('submission_key_hash', $keyHash)->firstOrFail(), $tokenHash, $payloadHash);
        }
    }

    public function accept(CustomerOrderRequest $request, User $actor): Order
    {
        $orderId = DB::transaction(function () use ($request, $actor): int {
            $this->assertActor($actor);
            $locked = CustomerOrderRequest::lockForUpdate()->findOrFail($request->id);
            if ($locked->order_id !== null) {
                return $locked->order_id;
            }
            if ($locked->status !== 'pending') {
                throw ValidationException::withMessages(['request' => 'Yêu cầu không còn chờ tiếp nhận.']);
            }
            $items = $locked->items()->with('toppings')->orderBy('id')->lockForUpdate()->get();
            $draft = [];
            foreach ($items as $item) {
                $draft[$item->id] = ['variant_id' => $item->variant_id, 'quantity' => $item->quantity,
                    'toppings' => $item->toppings->pluck('quantity', 'topping_id')->all(), 'note' => $item->note];
            }
            $quote = $this->review($draft, true, $locked->promotion_code);
            if ($items->isEmpty() || $quote['total'] !== $locked->quoted_total
                || $quote['promotion_discount'] !== $locked->promotion_discount
                || $quote['promotion_code'] !== $locked->promotion_code
                || $locked->delivery_fee !== self::DELIVERY_FEE) {
                throw ValidationException::withMessages(['request' => 'Món, giá hoặc phí đã đổi. Liên hệ khách kiểm tra và gửi yêu cầu mới.']);
            }
            foreach ($items as $item) {
                $line = $quote['items'][$item->id];
                $oldToppings = $item->toppings->map(fn ($row): array => [
                    'id' => $row->topping_id, 'name' => $row->topping_name, 'price' => $row->price, 'quantity' => $row->quantity,
                ])->sortBy('id')->values()->all();
                $newToppings = collect($line['toppings'])->map(fn (array $row): array => [
                    'id' => $row['id'], 'name' => $row['name'], 'price' => $row['price'], 'quantity' => $row['quantity'],
                ])->sortBy('id')->values()->all();
                if (! $line['available'] || $line['name'] !== $item->product_name || $line['variant_name'] !== $item->variant_name
                    || $line['unit_price'] !== $item->unit_price || $line['subtotal'] !== $item->subtotal || $oldToppings !== $newToppings) {
                    throw ValidationException::withMessages(['request' => 'Cấu hình hoặc giá món đã đổi. Liên hệ khách gửi yêu cầu mới.']);
                }
            }

            $order = $this->orders->createForDelivery($actor, $locked->note, $locked->delivery_fee);
            foreach ($draft as $line) {
                $item = $this->orders->addItem($order, $line, $actor);
                foreach ($line['toppings'] as $id => $quantity) {
                    $this->orders->addItemTopping($order, $item, ['topping_id' => $id, 'quantity' => $quantity], $actor);
                }
            }
            if ($locked->promotion_code !== null) {
                $this->promotions->applyToOrder($order, $locked->promotion_code);
            }
            if ($order->fresh()->total_amount !== $locked->quoted_total) {
                throw new DomainException('Tổng Order không khớp yêu cầu đã chốt.');
            }
            $locked->forceFill(['status' => 'accepted', 'order_id' => $order->id,
                'handled_by' => $actor->id, 'handled_at' => now()])->saveOrFail();

            return $order->id;
        }, 3);

        // Read after commit: a concurrent accept may have created the linked Order
        // after this transaction's first consistent read under MySQL REPEATABLE READ.
        return Order::findOrFail($orderId);
    }

    public function prepareCod(CustomerOrderRequest $request, User $actor): void
    {
        DB::transaction(function () use ($request, $actor): void {
            [$locked, $order] = $this->lockAccepted($request, $actor);
            if ($locked->payment_option !== 'cod') {
                throw ValidationException::withMessages(['request' => 'Đơn chuyển khoản phải xác nhận tiền qua màn hình thanh toán.']);
            }
            foreach ($order->orderItems()->orderBy('id')->lockForUpdate()->get() as $item) {
                if ($item->status === 'pending') {
                    $this->orders->transitionItemStatus($order, $item, 'preparing', $actor);
                }
            }
        }, 3);
    }

    public function dispatch(CustomerOrderRequest $request, User $actor): void
    {
        DB::transaction(function () use ($request, $actor): void {
            [$locked, $order] = $this->lockAccepted($request, $actor);
            if ($locked->dispatched_at !== null) {
                return;
            }
            $this->assertReadyForDelivery($order);
            if ($locked->payment_option === 'bank_transfer_prepaid' && ! $order->payments()->where('status', 'paid')->exists()) {
                throw ValidationException::withMessages(['payment' => 'Chưa xác nhận đủ tiền chuyển khoản.']);
            }
            $locked->forceFill(['dispatched_at' => now()])->saveOrFail();
        }, 3);
    }

    public function confirmDelivery(CustomerOrderRequest $request, array $attributes, User $actor): void
    {
        DB::transaction(function () use ($request, $attributes, $actor): void {
            [$locked, $order] = $this->lockAccepted($request, $actor);
            if ($locked->delivered_at !== null) {
                return;
            }
            if (! in_array($attributes['delivery_confirmation'] ?? null, [true, 1, '1', 'true', 'yes', 'on'], true)
                || $locked->dispatched_at === null) {
                throw ValidationException::withMessages(['request' => 'Phải bắt đầu giao và xác nhận khách thực sự đã nhận món.']);
            }
            $this->assertReadyForDelivery($order);
            if ($locked->payment_option === 'bank_transfer_prepaid' && ! $order->payments()->where('status', 'paid')->exists()) {
                throw ValidationException::withMessages(['payment' => 'Chưa xác nhận đủ tiền chuyển khoản.']);
            }
            $locked->forceFill(['delivered_at' => now()])->saveOrFail();
            foreach ($order->orderItems()->orderBy('id')->lockForUpdate()->get() as $item) {
                $this->orders->transitionItemStatus($order, $item, 'served', $actor);
            }
            if ($locked->payment_option === 'cod') {
                $this->payments->recordCustomerCodReceipt($order, $attributes, $actor);
            }
        }, 3);
    }

    private function lockAccepted(CustomerOrderRequest $request, User $actor): array
    {
        $this->assertActor($actor);
        $order = Order::lockForUpdate()->find($request->order_id);
        $locked = CustomerOrderRequest::lockForUpdate()->findOrFail($request->id);
        if ($order === null || $locked->status !== 'accepted' || $locked->order_id !== $order->id) {
            throw ValidationException::withMessages(['request' => 'Yêu cầu chưa được tiếp nhận thành đơn hàng.']);
        }

        return [$locked, $order];
    }

    private function assertReadyForDelivery(Order $order): void
    {
        $items = $order->orderItems()->orderBy('id')->lockForUpdate()->get();
        if ($order->status !== 'pending' || $items->isEmpty() || $items->contains(fn ($item): bool => $item->status !== 'ready')) {
            throw ValidationException::withMessages(['order' => 'Mọi món phải sẵn sàng trước khi giao và xác nhận nhận món.']);
        }
    }

    private function existingRequest(CustomerOrderRequest $request, string $tokenHash, string $payloadHash): CustomerOrderRequest
    {
        if (! hash_equals($request->access_token_hash, $tokenHash) || ! hash_equals($request->payload_hash, $payloadHash)
            || $request->access_expires_at->isPast()) {
            throw ValidationException::withMessages(['cart' => 'Phiên gửi yêu cầu không hợp lệ hoặc đã hết hạn.']);
        }

        return $request;
    }

    private function assertActor(User $actor): void
    {
        $current = User::find($actor->id);
        if ($current === null || $current->status !== 'active' || ! in_array($current->role, ['admin', 'staff'], true)) {
            throw new DomainException('Chỉ Admin hoặc Staff đang hoạt động được xử lý yêu cầu.');
        }
    }

    private function addFee(string $subtotal, string $discount = '0.00'): string
    {
        [$whole, $fraction] = explode('.', $subtotal);
        $cents = ((int) $whole * 100) + (int) $fraction;
        [$discountWhole, $discountFraction] = explode('.', $discount);
        $discountCents = ((int) $discountWhole * 100) + (int) $discountFraction;
        if ($discountCents > $cents) {
            throw new DomainException('Giảm giá không thể lớn hơn tiền món.');
        }
        if ($cents > 999999999999999 - 2000000) {
            throw ValidationException::withMessages(['cart' => 'Tổng cộng phí giao vượt giới hạn dữ liệu.']);
        }
        $cents = $cents - $discountCents + 2000000;

        return intdiv($cents, 100).'.'.str_pad((string) ($cents % 100), 2, '0', STR_PAD_LEFT);
    }

    private function moneyLabel(string $amount): string
    {
        [$whole, $fraction] = explode('.', $amount);

        return preg_replace('/\B(?=(\d{3})+(?!\d))/', '.', $whole).','.$fraction.' ₫';
    }
}
