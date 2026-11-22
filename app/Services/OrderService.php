<?php

namespace App\Services;

use App\Models\Area;
use App\Models\CafeTable;
use App\Models\Customer;
use App\Models\OnlineOrderRequest;
use App\Models\CustomerOrderRequest;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderItemTopping;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Topping;
use App\Models\User;
use DomainException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class OrderService
{
    public function __construct(private readonly InventoryService $inventoryService) {}

    public function create(array $attributes, User $actor, ?string $operationCode = null): Order
    {
        for ($attempt = 0; $attempt < 3; $attempt++) {
            try {
                return DB::transaction(function () use ($attributes, $actor, $operationCode): Order {
                    $this->assertActiveStaff($actor);
                    $orderType = $attributes['order_type'];
                    $customerId = $attributes['customer_id'] ?? null;

                    if ($customerId !== null) {
                        $customer = Customer::query()->lockForUpdate()->find($customerId);
                        if ($customer === null || $customer->status !== 'active') {
                            throw ValidationException::withMessages(['customer_id' => 'Khách hàng không còn hợp lệ hoặc đã ngừng hoạt động.']);
                        }
                    }

                    $tableId = null;
                    if ($orderType === 'dine_in') {
                        $tableId = (int) $attributes['table_id'];
                        $tableSnapshot = CafeTable::query()->find($tableId);
                        if ($tableSnapshot === null) {
                            throw ValidationException::withMessages(['table_id' => 'Bàn không còn tồn tại.']);
                        }

                        // Follow the existing table-management lock order: area, then table.
                        $area = Area::query()->lockForUpdate()->find($tableSnapshot->area_id);
                        $table = CafeTable::query()->lockForUpdate()->find($tableId);
                        if ($area === null || $area->status !== 'active' || $table === null || $table->status !== 'available') {
                            throw ValidationException::withMessages(['table_id' => 'Bàn không còn sẵn sàng để tạo đơn tại chỗ.']);
                        }

                        $activeOrderExists = Order::query()
                            ->where('table_id', $table->getKey())
                            ->where('order_type', 'dine_in')
                            ->where('status', 'pending')
                            ->lockForUpdate()
                            ->exists();

                        if ($activeOrderExists) {
                            throw ValidationException::withMessages(['table_id' => 'Bàn này đã có đơn đang xử lý.']);
                        }

                        $table->status = 'occupied';
                        $table->saveOrFail();
                    }

                    $purchaseType = $attributes['purchase_type'] ?? 'counter';
                    if (! in_array($purchaseType, ['counter', 'scheduled', 'online'], true)) {
                        throw ValidationException::withMessages(['purchase_type' => 'Loại mua không hợp lệ.']);
                    }

                    $onlineRequestId = isset($attributes['online_request_id']) ? (int) $attributes['online_request_id'] : null;
                    if ($purchaseType === 'counter') {
                        if ($onlineRequestId !== null) {
                            throw ValidationException::withMessages(['online_request_id' => 'Đơn tại quầy không được liên kết với yêu cầu trực tuyến.']);
                        }
                    } else {
                        if ($onlineRequestId === null) {
                            throw ValidationException::withMessages(['online_request_id' => 'Đơn trực tuyến/hẹn trước phải liên kết với yêu cầu đặt hàng.']);
                        }
                        if ($orderType !== 'takeaway' || $tableId !== null) {
                            throw ValidationException::withMessages(['order_type' => 'Đơn trực tuyến/hẹn trước chỉ hỗ trợ mang đi và không được gán bàn.']);
                        }
                    }

                    $orderData = [
                        'order_code' => $operationCode ?? $this->newOrderCode(),
                        'table_id' => $tableId,
                        'customer_id' => $customerId,
                        'user_id' => $actor->getKey(),
                        'reservation_id' => null,
                        'order_type' => $orderType,
                        'status' => 'pending',
                        'subtotal' => '0.00',
                        'discount_amount' => '0.00',
                        'tax_amount' => '0.00',
                        'total_amount' => '0.00',
                        'note' => $attributes['note'] ?? null,
                        'ordered_at' => now(),
                        'completed_at' => null,
                    ];

                    if (Schema::hasColumn('orders', 'purchase_type')) {
                        $orderData['purchase_type'] = $purchaseType;
                        $orderData['online_request_id'] = $onlineRequestId;
                    }

                    $order = new Order;
                    $order->forceFill($orderData);
                    $order->saveOrFail();

                    return $order;
                }, 3);
            } catch (UniqueConstraintViolationException $exception) {
                if ($attempt === 2) {
                    throw $exception;
                }
            }
        }

        throw new DomainException('Không thể tạo mã đơn hàng duy nhất.');
    }

    /**
     * Creates a pending order and all of its item snapshots as one POS workflow.
     * The POS controller supplies only variant IDs and quantities from its draft cart.
     *
     * @param  array<int, array{variant_id: mixed, quantity: mixed}>  $items
     */
    public function createWithItems(array $attributes, array $items, User $actor, ?string $operationCode = null): Order
    {
        if ($items === []) {
            throw ValidationException::withMessages(['cart' => 'Chưa có món trong đơn.']);
        }

        return DB::transaction(function () use ($attributes, $items, $actor, $operationCode): Order {
            foreach ($items as $item) {
                if (! is_array($item)
                    || ! isset($item['variant_id'], $item['quantity'])
                    || ! ctype_digit((string) $item['variant_id'])
                    || ! ctype_digit((string) $item['quantity'])
                    || (int) $item['variant_id'] < 1
                    || (int) $item['quantity'] < 1
                    || (int) $item['quantity'] > 2147483647) {
                    throw ValidationException::withMessages(['cart' => 'Giỏ hàng không hợp lệ.']);
                }
            }

            $order = $this->create($attributes, $actor, $operationCode);

            foreach ($items as $item) {
                $this->addItem($order, [
                    'variant_id' => (int) $item['variant_id'],
                    'quantity' => (int) $item['quantity'],
                ], $actor);
            }

            return Order::query()->findOrFail($order->getKey());
        }, 3);
    }

    /** Server-generated intent; actor row serializes simultaneous POS submissions. */
    public function createPosOnce(array $attributes, array $items, User $actor, string $key): Order
    {
        return DB::transaction(function () use ($attributes, $items, $actor, $key): Order {
            $this->assertActiveStaff($actor);
            $code = 'POS-'.substr(hash('sha256', $actor->getKey().':'.$key), 0, 40);
            $existing = Order::where('order_code', $code)->first();
            if ($existing) {
                $expected = collect($items)->map(fn ($line) => [(int) $line['variant_id'], (int) $line['quantity']])->sort()->values()->all();
                $actual = $existing->orderItems()->get()->map(fn ($line) => [(int) $line->variant_id, (int) $line->quantity])->sort()->values()->all();
                if ($expected !== $actual || $existing->order_type !== $attributes['order_type']
                    || (string) $existing->table_id !== (string) (($attributes['order_type'] === 'dine_in') ? ($attributes['table_id'] ?? null) : null)
                    || (string) $existing->note !== (string) ($attributes['note'] ?? null)) {
                    throw ValidationException::withMessages(['cart' => 'Thao tác tạo đơn đã được dùng với nội dung khác.']);
                }

                return $existing;
            }

            return $this->createWithItems(array_replace($attributes, ['purchase_type' => 'counter']), $items, $actor, $code);
        }, 3);
    }

    /** Web delivery is explicitly mapped to takeaway, with no table or Customer master link. */
    public function createForDelivery(User $actor, ?string $note, string $deliveryFee): Order
    {
        return DB::transaction(function () use ($actor, $note, $deliveryFee): Order {
            $order = $this->create(['order_type' => 'takeaway', 'note' => $note], $actor);
            $order->forceFill(['delivery_fee' => $this->normalizeMoney($deliveryFee)]);
            $this->recalculateTotals($order);

            return $order;
        });
    }

    /**
     * Cancel a pending order without deleting its historical item or topping snapshots.
     * Inventory is intentionally not restored here: only pending items are cancellable.
     */
    public function cancel(Order $order, User $actor): Order
    {
        return DB::transaction(function () use ($order, $actor): Order {
            $this->assertActiveStaff($actor);

            $lockedOrder = $this->lockLifecycleOrder($order);
            if ($lockedOrder === null || $lockedOrder->status !== 'pending') {
                throw ValidationException::withMessages(['order' => 'Chỉ có thể hủy đơn đang chờ xử lý.']);
            }
            $this->assertUnpaidForMutation($lockedOrder);

            $items = OrderItem::query()
                ->where('order_id', $lockedOrder->getKey())
                ->lockForUpdate()
                ->get(['id', 'status']);

            if ($items->contains(fn (OrderItem $item): bool => $item->status !== 'pending')) {
                throw ValidationException::withMessages(['order_item' => 'Không thể hủy đơn đã có món đang hoặc đã pha chế.']);
            }

            if (Schema::hasTable('payments') && $lockedOrder->payments()->where('status', 'paid')->lockForUpdate()->first() !== null) {
                throw ValidationException::withMessages(['order' => 'Đơn đã thu tiền cần quán xử lý thủ công, không tự hủy.']);
            }

            $lockedOrder->forceFill(['status' => 'cancelled']);
            $lockedOrder->saveOrFail();

            $this->releaseTableIfFree($lockedOrder);

            return $lockedOrder;
        }, 3);
    }

    /**
     * Accept an online or scheduled order request and create the corresponding Order.
     * Idempotency: if already accepted, returns the existing Order without double creation.
     */
    public function acceptOnlineRequest(OnlineOrderRequest $request, User $actor): Order
    {
        return DB::transaction(function () use ($request, $actor): Order {
            $this->assertActiveStaff($actor);

            $lockedRequest = OnlineOrderRequest::query()->lockForUpdate()->find($request->getKey());
            if ($lockedRequest === null) {
                throw ValidationException::withMessages(['request' => 'Yêu cầu không tồn tại.']);
            }

            if ($lockedRequest->status === 'accepted') {
                $existingOrder = Order::query()->where('online_request_id', $lockedRequest->id)->first();
                if ($existingOrder !== null) {
                    return $existingOrder;
                }
            }

            if ($lockedRequest->status !== 'pending') {
                throw ValidationException::withMessages(['request' => 'Chỉ có thể tiếp nhận yêu cầu đang ở trạng thái chờ duyệt.']);
            }

            if ($lockedRequest->purchase_type === 'scheduled') {
                app(ScheduledPickupService::class)->validateTime(
                    $lockedRequest->pickup_at->timezone('Asia/Ho_Chi_Minh')->format('Y-m-d\TH:i')
                );
            }

            $lockedRequest->load(['items.toppings', 'items.variant.product.category']);
            if ($lockedRequest->items->isEmpty()) {
                throw ValidationException::withMessages(['request' => 'Yêu cầu không có món nào.']);
            }

            // Verify variants, toppings, availability, and reconfirm prices
            foreach ($lockedRequest->items as $item) {
                $variant = ProductVariant::query()->lockForUpdate()->find($item->variant_id);
                if ($variant === null || $variant->status !== 'available' || $variant->trashed()) {
                    throw ValidationException::withMessages(['variant' => 'Món "'.$item->product_name.'" hiện không còn khả dụng để phục vụ.']);
                }

                $product = Product::query()->lockForUpdate()->find($variant->product_id);
                if ($product === null || $product->status !== 'available' || $product->trashed()) {
                    throw ValidationException::withMessages(['product' => 'Sản phẩm "'.$item->product_name.'" hiện không còn khả dụng.']);
                }

                if (! $product->category || $product->category->status !== 'active' || $product->category->trashed()) {
                    throw ValidationException::withMessages(['product' => 'Danh mục đã ngừng bán.']);
                }

                $currentUnitPrice = $this->normalizeMoney($variant->getRawOriginal('price'));
                $snapshotUnitPrice = $this->normalizeMoney($item->getRawOriginal('unit_price'));
                if ($currentUnitPrice !== $snapshotUnitPrice) {
                    throw ValidationException::withMessages(['price' => 'Giá món "'.$item->product_name.'" đã thay đổi từ '.$snapshotUnitPrice.' sang '.$currentUnitPrice.'. Vui lòng liên hệ khách xác nhận lại.']);
                }

                foreach ($item->toppings as $toppingItem) {
                    $topping = Topping::query()->lockForUpdate()->find($toppingItem->topping_id);
                    if ($topping === null || $topping->status !== 'available' || $topping->trashed()) {
                        throw ValidationException::withMessages(['topping' => 'Topping "'.$toppingItem->topping_name.'" hiện không còn khả dụng.']);
                    }

                    $currentToppingPrice = $this->normalizeMoney($topping->getRawOriginal('price'));
                    $snapshotToppingPrice = $this->normalizeMoney($toppingItem->getRawOriginal('price'));
                    if ($currentToppingPrice !== $snapshotToppingPrice) {
                        throw ValidationException::withMessages(['price' => 'Giá topping "'.$toppingItem->topping_name.'" đã thay đổi. Vui lòng liên hệ khách xác nhận lại.']);
                    }
                }
            }

            $lockedRequest->forceFill([
                'status' => 'accepted',
                'accepted_by' => $actor->getKey(),
                'accepted_at' => now(),
            ]);
            $lockedRequest->saveOrFail();

            $order = $this->create([
                'order_type' => 'takeaway',
                'purchase_type' => $lockedRequest->purchase_type,
                'online_request_id' => $lockedRequest->id,
                'customer_id' => $lockedRequest->customer_id,
                'note' => $lockedRequest->note,
            ], $actor);

            foreach ($lockedRequest->items as $reqItem) {
                $orderItem = new OrderItem;
                $unitPrice = $this->normalizeMoney($reqItem->getRawOriginal('unit_price'));
                $quantity = (int) $reqItem->quantity;
                $lineSubtotal = $this->multiplyMoney($unitPrice, $quantity);

                $orderItem->forceFill([
                    'order_id' => $order->getKey(),
                    'product_id' => $reqItem->variant->product_id,
                    'variant_id' => $reqItem->variant_id,
                    'product_name' => $reqItem->product_name,
                    'variant_name' => $reqItem->variant_name,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'topping_amount' => '0.00',
                    'subtotal' => $lineSubtotal,
                    'note' => $reqItem->note,
                    'status' => 'pending',
                ]);
                $orderItem->saveOrFail();

                foreach ($reqItem->toppings as $reqTopping) {
                    $orderTopping = new OrderItemTopping;
                    $orderTopping->forceFill([
                        'order_item_id' => $orderItem->getKey(),
                        'topping_id' => $reqTopping->topping_id,
                        'topping_name' => $reqTopping->topping_name,
                        'price' => $this->normalizeMoney($reqTopping->getRawOriginal('price')),
                        'quantity' => (int) $reqTopping->quantity,
                    ]);
                    $orderTopping->saveOrFail();
                }

                $this->recalculateItemAndOrderTotals($order, $orderItem);
            }

            return Order::query()->findOrFail($order->getKey());
        }, 3);
    }

    /**
     * Reject an online or scheduled order request with a required reason.
     * Does NOT create an Order.
     */
    public function rejectOnlineRequest(OnlineOrderRequest $request, string $reason, User $actor): OnlineOrderRequest
    {
        return DB::transaction(function () use ($request, $reason, $actor): OnlineOrderRequest {
            $this->assertActiveStaff($actor);

            $lockedRequest = OnlineOrderRequest::query()->lockForUpdate()->find($request->getKey());
            if ($lockedRequest === null || $lockedRequest->status !== 'pending') {
                throw ValidationException::withMessages(['request' => 'Chỉ có thể từ chối yêu cầu đang ở trạng thái chờ duyệt.']);
            }

            $trimmedReason = trim($reason);
            if ($trimmedReason === '') {
                throw ValidationException::withMessages(['rejection_reason' => 'Lý do từ chối là bắt buộc.']);
            }

            $lockedRequest->forceFill([
                'status' => 'rejected',
                'rejection_reason' => $trimmedReason,
            ]);
            $lockedRequest->saveOrFail();

            return $lockedRequest;
        }, 3);
    }

    /**
     * Complete an order.
     * Condition: All items must be served AND order must be fully paid.
     */
    public function complete(Order $order, User $actor): Order
    {
        return DB::transaction(function () use ($order, $actor): Order {
            $this->assertActiveStaff($actor);

            $lockedOrder = $this->lockLifecycleOrder($order);
            if ($lockedOrder?->status === 'completed') {
                return $lockedOrder;
            }
            if ($lockedOrder === null || $lockedOrder->status !== 'pending') {
                throw ValidationException::withMessages(['order' => 'Chỉ có thể hoàn tất đơn đang chờ xử lý.']);
            }

            $items = OrderItem::query()
                ->where('order_id', $lockedOrder->getKey())
                ->lockForUpdate()
                ->get();

            if ($items->isEmpty() || $items->contains(fn (OrderItem $item): bool => $item->status !== 'served')) {
                throw ValidationException::withMessages(['order' => 'Chỉ có thể hoàn tất đơn khi tất cả các món đã được phục vụ/giao (served).']);
            }

            if (app(PaymentService::class)->settlementForOrder($lockedOrder)['payment_status'] !== 'paid') {
                throw ValidationException::withMessages(['order' => 'Chỉ có thể hoàn tất đơn khi đã thu đủ tiền.']);
            }

            $lockedOrder->forceFill([
                'status' => 'completed',
                'completed_at' => now(),
            ]);
            $lockedOrder->saveOrFail();

            if ($lockedOrder->order_type === 'dine_in' && $lockedOrder->table_id !== null) {
                $table = CafeTable::withTrashed()->lockForUpdate()->find($lockedOrder->table_id);
                if ($table !== null && ! $table->trashed() && $table->status === 'occupied') {
                    $hasAnotherPending = Order::query()
                        ->where('table_id', $table->getKey())
                        ->where('order_type', 'dine_in')
                        ->where('status', 'pending')
                        ->where('id', '!=', $lockedOrder->getKey())
                        ->lockForUpdate()
                        ->exists();

                    if (! $hasAnotherPending) {
                        $table->forceFill(['status' => 'available'])->saveOrFail();
                    }
                }
            }

            return $lockedOrder;
        }, 3);
    }

    /**
     * Record cash or bank settlement for an order at counter or upon pickup.
     */
    public function recordPayment(Order $order, array $attributes, User $actor): Payment
    {
        return app(PaymentService::class)->recordPaid($order, $attributes, $actor);
    }

    /**
     * Add one sellable variant to a pending order and refresh its server-owned totals.
     * Toppings and item-status transitions are deliberately handled by later workflows.
     */
    public function addItem(Order $order, array $attributes, User $actor): OrderItem
    {
        return DB::transaction(function () use ($order, $attributes, $actor): OrderItem {
            $this->assertActiveStaff($actor);

            $lockedOrder = Order::query()->lockForUpdate()->find($order->getKey());
            if ($lockedOrder === null || $lockedOrder->status !== 'pending') {
                throw ValidationException::withMessages(['order' => 'Chỉ có thể thêm món vào đơn đang chờ xử lý.']);
            }
            $this->assertUnpaidForMutation($lockedOrder);
            $this->assertCustomerConfigurationUnchanged($lockedOrder);

            if (Schema::hasTable('payments') && $lockedOrder->payments()->where('status', 'paid')->exists()) {
                throw ValidationException::withMessages(['order' => 'Không thêm món sau khi đã thu tiền.']);
            }

            $variant = ProductVariant::query()->lockForUpdate()->find($attributes['variant_id']);
            $product = $variant === null
                ? null
                : Product::query()->lockForUpdate()->find($variant->product_id);

            if ($product === null || $product->status !== 'available') {
                throw ValidationException::withMessages(['variant_id' => 'Sản phẩm của biến thể này không còn sẵn sàng để bán.']);
            }

            if (! $product->category || $product->category->trashed() || $product->category->status !== 'active') {
                throw ValidationException::withMessages(['variant_id' => 'Danh mục sản phẩm đã ngừng bán.']);
            }

            if ($variant === null || $variant->status !== 'available') {
                throw ValidationException::withMessages(['variant_id' => 'Biến thể không hợp lệ hoặc không còn sẵn sàng để bán.']);
            }

            $quantity = (int) $attributes['quantity'];
            $unitPrice = $this->normalizeMoney($variant->getRawOriginal('price'));
            $lineSubtotal = $this->multiplyMoney($unitPrice, $quantity);

            $item = new OrderItem;
            $item->forceFill([
                'order_id' => $lockedOrder->getKey(),
                'product_id' => $product->getKey(),
                'variant_id' => $variant->getKey(),
                'product_name' => $product->name,
                'variant_name' => $variant->name,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'topping_amount' => '0.00',
                'subtotal' => $lineSubtotal,
                'note' => $attributes['note'] ?? null,
                'status' => 'pending',
            ]);
            $item->saveOrFail();

            $this->recalculateTotals($lockedOrder);

            return $item;
        }, 3);
    }

    public function updateItemQuantity(Order $order, OrderItem $item, array $attributes, User $actor): OrderItem
    {
        return DB::transaction(function () use ($order, $item, $attributes, $actor): OrderItem {
            $this->assertActiveStaff($actor);
            $lockedOrder = $this->lockedPendingOrder($order);
            $lockedItem = $this->lockedPendingItem($lockedOrder, $item);
            $quantity = (int) $attributes['quantity'];

            $lockedItem->forceFill([
                'quantity' => $quantity,
                'subtotal' => $this->lineSubtotal(
                    $lockedItem->getRawOriginal('unit_price'),
                    $quantity,
                    $lockedItem->getRawOriginal('topping_amount'),
                ),
            ]);
            $lockedItem->saveOrFail();

            $this->recalculateTotals($lockedOrder);

            return $lockedItem;
        }, 3);
    }

    public function removeItem(Order $order, OrderItem $item, User $actor): void
    {
        DB::transaction(function () use ($order, $item, $actor): void {
            $this->assertActiveStaff($actor);
            $lockedOrder = $this->lockedPendingOrder($order);
            $lockedItem = $this->lockedPendingItem($lockedOrder, $item);

            $lockedItem->deleteOrFail();
            $this->recalculateTotals($lockedOrder);
        }, 3);
    }

    public function addItemTopping(Order $order, OrderItem $item, array $attributes, User $actor): OrderItemTopping
    {
        return DB::transaction(function () use ($order, $item, $attributes, $actor): OrderItemTopping {
            $this->assertActiveStaff($actor);
            $lockedOrder = $this->lockedPendingOrder($order);
            $lockedItem = $this->lockedPendingItem($lockedOrder, $item);

            $topping = Topping::query()->lockForUpdate()->find($attributes['topping_id']);
            if ($topping === null || $topping->status !== 'available') {
                throw ValidationException::withMessages(['topping_id' => 'Topping không hợp lệ hoặc hiện không sẵn sàng.']);
            }

            $quantity = (int) $attributes['quantity'];
            $itemTopping = OrderItemTopping::query()
                ->where('order_item_id', $lockedItem->getKey())
                ->where('topping_id', $topping->getKey())
                ->lockForUpdate()
                ->first();

            if ($itemTopping === null) {
                $itemTopping = new OrderItemTopping;
                $itemTopping->forceFill([
                    'order_item_id' => $lockedItem->getKey(),
                    'topping_id' => $topping->getKey(),
                    'topping_name' => $topping->name,
                    'price' => $this->normalizeMoney($topping->getRawOriginal('price')),
                    'quantity' => $quantity,
                ]);
            } else {
                if ($quantity > 2147483647 - $itemTopping->quantity) {
                    throw ValidationException::withMessages(['quantity' => 'Số lượng topping vượt quá giới hạn cho phép.']);
                }

                // Preserve the original name and price snapshot after a topping is sold.
                $itemTopping->quantity += $quantity;
            }

            $itemTopping->saveOrFail();
            $this->recalculateItemAndOrderTotals($lockedOrder, $lockedItem);

            return $itemTopping;
        }, 3);
    }

    public function removeItemTopping(Order $order, OrderItem $item, OrderItemTopping $itemTopping, User $actor): void
    {
        DB::transaction(function () use ($order, $item, $itemTopping, $actor): void {
            $this->assertActiveStaff($actor);
            $lockedOrder = $this->lockedPendingOrder($order);
            $lockedItem = $this->lockedPendingItem($lockedOrder, $item);
            $lockedItemTopping = OrderItemTopping::query()
                ->whereKey($itemTopping->getKey())
                ->where('order_item_id', $lockedItem->getKey())
                ->lockForUpdate()
                ->first();

            if ($lockedItemTopping === null) {
                throw ValidationException::withMessages(['order_item_topping' => 'Topping không thuộc món trong đơn hàng này.']);
            }

            $lockedItemTopping->deleteOrFail();
            $this->recalculateItemAndOrderTotals($lockedOrder, $lockedItem);
        }, 3);
    }

    public function updateItemToppingQuantity(Order $order, OrderItem $item, OrderItemTopping $itemTopping, array $attributes, User $actor): OrderItemTopping
    {
        return DB::transaction(function () use ($order, $item, $itemTopping, $attributes, $actor): OrderItemTopping {
            $this->assertActiveStaff($actor);
            $lockedOrder = $this->lockedPendingOrder($order);
            $lockedItem = $this->lockedPendingItem($lockedOrder, $item);
            $lockedItemTopping = OrderItemTopping::query()
                ->whereKey($itemTopping->getKey())
                ->where('order_item_id', $lockedItem->getKey())
                ->lockForUpdate()
                ->first();

            if ($lockedItemTopping === null) {
                throw ValidationException::withMessages(['order_item_topping' => 'Topping không thuộc món trong đơn hàng này.']);
            }

            $lockedItemTopping->forceFill(['quantity' => (int) $attributes['quantity']]);
            $lockedItemTopping->saveOrFail();
            $this->recalculateItemAndOrderTotals($lockedOrder, $lockedItem);

            return $lockedItemTopping;
        }, 3);
    }

    public function transitionItemStatus(Order $order, OrderItem $item, string $nextStatus, User $actor, ?string $pickupCode = null): OrderItem
    {
        return DB::transaction(function () use ($order, $item, $nextStatus, $actor, $pickupCode): OrderItem {
            $this->assertActiveStaff($actor);
            $lockedOrder = $this->lockedPendingOrder($order, true);
            $lockedItem = $this->lockedItemForStatus($lockedOrder, $item);
            $customerRequest = Schema::hasTable('customer_order_requests')
                ? CustomerOrderRequest::where('order_id', $lockedOrder->id)->first() : null;

            if ($nextStatus === 'preparing') {
                if ($lockedItem->status !== 'pending') {
                    throw ValidationException::withMessages(['order_item' => 'Món phải ở trạng thái chờ trước khi bắt đầu pha chế.']);
                }
                if ($customerRequest?->payment_option === 'bank_transfer_prepaid'
                    && ! Payment::where('order_id', $lockedOrder->id)->where('status', 'paid')->exists()) {
                    throw ValidationException::withMessages(['payment' => 'Đơn web chuyển khoản phải được xác nhận nhận đủ tiền trước khi pha chế.']);
                }

                $this->inventoryService->startPreparing($lockedItem, $actor);

                return OrderItem::query()->findOrFail($lockedItem->getKey());
            }

            $expectedCurrentStatus = match ($nextStatus) {
                'ready' => 'preparing',
                'served' => 'ready',
                default => null,
            };

            if ($expectedCurrentStatus === null || $lockedItem->status !== $expectedCurrentStatus) {
                throw ValidationException::withMessages(['status' => 'Chuyển trạng thái món không hợp lệ.']);
            }
            if ($nextStatus === 'served' && $customerRequest !== null && $customerRequest->delivered_at === null) {
                throw ValidationException::withMessages(['order_item' => 'Đơn giao web chỉ phục vụ xong qua thao tác xác nhận giao thành công.']);
            }

            if ($nextStatus === 'served') {
                // COD delivery records served and its receipt atomically in CustomerOrderService.
                $collectingCod = $customerRequest?->payment_option === 'cod' && $customerRequest->delivered_at !== null;
                if (! $collectingCod && (! Schema::hasTable('payments') || app(PaymentService::class)->settlementForOrder($lockedOrder)['payment_status'] !== 'paid')) {
                    throw ValidationException::withMessages(['payment' => 'Phải thu đủ tiền trước khi giao món.']);
                }
                $request = $lockedOrder->onlineOrderRequest;
                if ($request && ! hash_equals($request->request_code, (string) $pickupCode)) {
                    throw ValidationException::withMessages(['pickup_code' => 'Mã nhận đồ không hợp lệ.']);
                }
            }

            $lockedItem->forceFill(['status' => $nextStatus]);
            $lockedItem->saveOrFail();

            if ($nextStatus === 'served') {
                $this->completePaidOrderIfAllServed($lockedOrder);
            }

            return $lockedItem;
        }, 3);
    }

    /** The caller must hold the Order transaction; paid alone never completes an unserved Order. */
    public function completePaidOrderIfAllServed(Order $order): bool
    {
        if (DB::transactionLevel() === 0) {
            throw new DomainException('Cần transaction để hoàn tất đơn đã thanh toán.');
        }

        $lockedOrder = Order::query()->lockForUpdate()->findOrFail($order->getKey());
        if ($lockedOrder->status === 'completed') {
            return false;
        }
        if ($lockedOrder->status !== 'pending') {
            throw ValidationException::withMessages(['order' => 'Đơn không ở trạng thái có thể hoàn tất.']);
        }

        $items = OrderItem::query()->where('order_id', $lockedOrder->getKey())
            ->orderBy('id')->lockForUpdate()->get(['id', 'status']);
        if (! $items->contains('status', 'served')
            || $items->contains(fn (OrderItem $item): bool => ! in_array($item->status, ['served', 'cancelled'], true))
            || app(PaymentService::class)->settlementForOrder($lockedOrder)['payment_status'] !== 'paid') {
            return false;
        }

        $lockedOrder->forceFill(['status' => 'completed', 'completed_at' => now()])->saveOrFail();
        $this->releaseTableIfFree($lockedOrder);

        return true;
    }

    private function releaseTableIfFree(Order $order): void
    {
        if ($order->order_type !== 'dine_in' || $order->table_id === null) {
            return;
        }

        $table = CafeTable::withTrashed()->lockForUpdate()->find($order->table_id);
        if ($table === null || $table->trashed() || $table->status !== 'occupied') {
            return;
        }

        $anotherPendingOrder = Order::query()->where('table_id', $table->getKey())
            ->where('order_type', 'dine_in')->where('status', 'pending')
            ->where('id', '!=', $order->getKey())->lockForUpdate()->exists();
        if (! $anotherPendingOrder) {
            $table->forceFill(['status' => 'available'])->saveOrFail();
        }
    }

    private function assertActiveStaff(User $actor): void
    {
        $currentActor = User::query()->lockForUpdate()->find($actor->getKey());
        if ($currentActor === null || ! $currentActor->canOperateSales()) {
            throw new DomainException('Chỉ Admin hoặc Staff đang hoạt động được thao tác đơn hàng.');
        }
    }

    /** Match the area -> table -> Order lock order before a lifecycle releases a table. */
    private function lockLifecycleOrder(Order $order): ?Order
    {
        $snapshot = Order::query()->find($order->getKey());
        if ($snapshot?->order_type === 'dine_in' && $snapshot->table_id !== null) {
            $table = CafeTable::withTrashed()->find($snapshot->table_id);
            if ($table) {
                Area::withTrashed()->whereKey($table->area_id)->lockForUpdate()->first();
                $lockedTable = CafeTable::withTrashed()->whereKey($table->id)->lockForUpdate()->first();
                if (! $lockedTable || $lockedTable->area_id !== $table->area_id) {
                    throw ValidationException::withMessages(['order' => 'Bàn đã thay đổi. Vui lòng tải lại đơn.']);
                }
            }
        }
        $locked = Order::query()->lockForUpdate()->find($order->getKey());
        if ($locked && ($locked->table_id !== $snapshot?->table_id || $locked->order_type !== $snapshot?->order_type)) {
            throw ValidationException::withMessages(['order' => 'Bàn hoặc hình thức phục vụ đã thay đổi. Vui lòng tải lại đơn.']);
        }

        return $locked;
    }

    private function lockedPendingOrder(Order $order, bool $forLifecycle = false): Order
    {
        $lockedOrder = Order::query()->lockForUpdate()->find($order->getKey());
        if ($lockedOrder === null || $lockedOrder->status !== 'pending') {
            throw ValidationException::withMessages(['order' => 'Chỉ có thể chỉnh sửa món trong đơn đang chờ xử lý.']);
        }

        if (! $forLifecycle) {
            $this->assertUnpaidForMutation($lockedOrder);
            $this->assertCustomerConfigurationUnchanged($lockedOrder);
        }

        return $lockedOrder;
    }

    private function assertUnpaidForMutation(Order $order): void
    {
        if (Payment::query()->where('order_id', $order->getKey())
            ->where('status', 'paid')->lockForUpdate()->exists()) {
            throw ValidationException::withMessages(['order' => 'Đơn đã thanh toán không thể sửa món hoặc hủy.']);
        }
    }

    private function assertCustomerConfigurationUnchanged(Order $order): void
    {
        if (Schema::hasTable('customer_order_requests') && CustomerOrderRequest::where('order_id', $order->id)->exists()) {
            throw ValidationException::withMessages(['order' => 'Cấu hình đơn web đã chốt với khách; không thể sửa món hoặc topping.']);
        }
    }

    private function lockedPendingItem(Order $order, OrderItem $item): OrderItem
    {
        $lockedItem = OrderItem::query()
            ->whereKey($item->getKey())
            ->where('order_id', $order->getKey())
            ->lockForUpdate()
            ->first();

        if ($lockedItem === null) {
            throw ValidationException::withMessages(['order_item' => 'Món không thuộc đơn hàng này.']);
        }

        if ($lockedItem->status !== 'pending') {
            throw ValidationException::withMessages(['order_item' => 'Chỉ có thể chỉnh sửa món đang chờ pha chế.']);
        }

        if (Schema::hasTable('payments') && $order->payments()->where('status', 'paid')->exists()) {
            throw ValidationException::withMessages(['order' => 'Không đổi món hoặc tổng tiền sau khi đã thu tiền.']);
        }

        return $lockedItem;
    }

    private function lockedItemForStatus(Order $order, OrderItem $item): OrderItem
    {
        $lockedItem = OrderItem::query()
            ->whereKey($item->getKey())
            ->where('order_id', $order->getKey())
            ->lockForUpdate()
            ->first();

        if ($lockedItem === null) {
            throw ValidationException::withMessages(['order_item' => 'Món không thuộc đơn hàng này.']);
        }

        return $lockedItem;
    }

    private function newOrderCode(): string
    {
        return 'ORD-'.now()->format('YmdHis').'-'.Str::upper(Str::random(8));
    }

    /** Payment caller already holds the Order lock inside its transaction. */
    public function refreshTotalsForPayment(Order $order): void
    {
        if (DB::transactionLevel() === 0) {
            throw new DomainException('Cần transaction để xác minh tổng thanh toán.');
        }

        $lockedOrder = Order::query()->lockForUpdate()->findOrFail($order->getKey());
        if ($lockedOrder->status !== 'pending') {
            throw new DomainException('Đơn không còn ở trạng thái thanh toán.');
        }

        $this->recalculateTotals($lockedOrder);
        $order->refresh();
    }

    private function recalculateTotals(Order $order): void
    {
        $subtotalInCents = 0;
        $maximumOrderAmountInCents = 99999999999999999;

        OrderItem::query()
            ->where('order_id', $order->getKey())
            ->lockForUpdate()
            ->get(['subtotal'])
            ->each(function (OrderItem $item) use (&$subtotalInCents, $maximumOrderAmountInCents): void {
                $itemSubtotalInCents = $this->moneyToCents($item->getRawOriginal('subtotal'));
                if ($itemSubtotalInCents > $maximumOrderAmountInCents - $subtotalInCents) {
                    throw ValidationException::withMessages(['quantity' => 'Tổng tiền đơn hàng vượt quá giới hạn cho phép.']);
                }

                $subtotalInCents += $itemSubtotalInCents;
            });

        $discountInCents = $this->moneyToCents($order->getRawOriginal('discount_amount'));
        $taxInCents = $this->moneyToCents($order->getRawOriginal('tax_amount'));
        $deliveryFeeInCents = $this->moneyToCents($order->getAttribute('delivery_fee') ?? '0.00');
        if ($discountInCents > $subtotalInCents) {
            throw new DomainException('Giảm giá hiện tại không hợp lệ với tổng tiền món.');
        }

        $order->forceFill([
            'subtotal' => $this->moneyFromCents($subtotalInCents),
            'total_amount' => $this->moneyFromCents($subtotalInCents - $discountInCents + $taxInCents + $deliveryFeeInCents),
        ]);
        $order->saveOrFail();
    }

    private function recalculateItemAndOrderTotals(Order $order, OrderItem $item): void
    {
        $toppingAmountInCents = 0;
        $maximumToppingAmountInCents = 999999999999;

        OrderItemTopping::query()
            ->where('order_item_id', $item->getKey())
            ->lockForUpdate()
            ->get(['price', 'quantity'])
            ->each(function (OrderItemTopping $itemTopping) use (&$toppingAmountInCents, $maximumToppingAmountInCents): void {
                $lineAmountInCents = $this->moneyToCents($this->multiplyMoney(
                    $itemTopping->getRawOriginal('price'),
                    $itemTopping->quantity,
                ));

                if ($lineAmountInCents > $maximumToppingAmountInCents - $toppingAmountInCents) {
                    throw ValidationException::withMessages(['quantity' => 'Tổng tiền topping vượt quá giới hạn cho phép.']);
                }

                $toppingAmountInCents += $lineAmountInCents;
            });

        $toppingAmount = $this->moneyFromCents($toppingAmountInCents);
        $item->forceFill([
            'topping_amount' => $toppingAmount,
            'subtotal' => $this->lineSubtotal(
                $item->getRawOriginal('unit_price'),
                $item->quantity,
                $toppingAmount,
            ),
        ]);
        $item->saveOrFail();

        $this->recalculateTotals($order);
    }

    private function multiplyMoney(string $amount, int $quantity): string
    {
        $unitPriceInCents = $this->moneyToCents($amount);
        $maximumOrderAmountInCents = 99999999999999999;

        if ($unitPriceInCents > intdiv($maximumOrderAmountInCents, $quantity)) {
            throw ValidationException::withMessages(['quantity' => 'Số lượng làm tổng tiền món vượt quá giới hạn cho phép.']);
        }

        return $this->moneyFromCents($unitPriceInCents * $quantity);
    }

    private function lineSubtotal(string $unitPrice, int $quantity, string $toppingAmount): string
    {
        $itemAmountInCents = $this->moneyToCents($this->multiplyMoney($unitPrice, $quantity));
        $toppingAmountInCents = $this->moneyToCents($toppingAmount);
        $maximumOrderAmountInCents = 99999999999999999;

        if ($toppingAmountInCents > $maximumOrderAmountInCents - $itemAmountInCents) {
            throw ValidationException::withMessages(['quantity' => 'Tổng tiền món vượt quá giới hạn cho phép.']);
        }

        return $this->moneyFromCents($itemAmountInCents + $toppingAmountInCents);
    }

    private function normalizeMoney(mixed $amount): string
    {
        $amount = trim((string) $amount);
        if (! preg_match('/^(0|[1-9]\d*)(?:\.(\d{1,2}))?$/', $amount, $matches)) {
            throw new DomainException('Giá bán của biến thể không hợp lệ.');
        }

        return $matches[1].'.'.str_pad($matches[2] ?? '', 2, '0');
    }

    private function moneyToCents(mixed $amount): int
    {
        [$whole, $fraction] = explode('.', $this->normalizeMoney($amount));

        return ((int) $whole * 100) + (int) $fraction;
    }

    private function moneyFromCents(int $amountInCents): string
    {
        if ($amountInCents < 0) {
            throw new DomainException('Tổng tiền không thể âm.');
        }

        return intdiv($amountInCents, 100).'.'.str_pad((string) ($amountInCents % 100), 2, '0', STR_PAD_LEFT);
    }
}
