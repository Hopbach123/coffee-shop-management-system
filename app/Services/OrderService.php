<?php

namespace App\Services;

use App\Models\Area;
use App\Models\CafeTable;
use App\Models\Customer;
use App\Models\Order;
use App\Models\User;
use DomainException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class OrderService
{
    public function create(array $attributes, User $actor): Order
    {
        for ($attempt = 0; $attempt < 3; $attempt++) {
            try {
                return DB::transaction(function () use ($attributes, $actor): Order {
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

                    $order = new Order();
                    $order->forceFill([
                        'order_code' => $this->newOrderCode(),
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
                    ]);
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

    private function assertActiveStaff(User $actor): void
    {
        $currentActor = User::query()->lockForUpdate()->find($actor->getKey());
        if ($currentActor === null || $currentActor->role !== 'staff' || $currentActor->status !== 'active') {
            throw new DomainException('Chỉ nhân viên đang hoạt động mới được tạo đơn hàng.');
        }
    }

    private function newOrderCode(): string
    {
        return 'ORD-'.now()->format('YmdHis').'-'.Str::upper(Str::random(8));
    }
}
