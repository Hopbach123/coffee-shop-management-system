<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\CafeTable;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderItemTopping;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class StaffOrderCancellationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->assertSame('sqlite', config('database.default'));
        foreach ([
            '0001_01_01_000000_create_users_table.php',
            '2026_08_14_000002_create_areas_table.php',
            '2026_08_14_000006_create_cafe_tables_table.php',
        ] as $migration) {
            (require database_path('migrations/'.$migration))->up();
        }

        Schema::create('orders', function (Blueprint $table): void {
            $table->id();
            $table->string('order_code', 50)->unique();
            $table->foreignId('table_id')->nullable()->constrained('cafe_tables')->restrictOnDelete();
            $table->foreignId('customer_id')->nullable();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('reservation_id')->nullable();
            $table->string('order_type');
            $table->string('status')->default('pending');
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->text('note')->nullable();
            $table->dateTime('ordered_at');
            $table->dateTime('completed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('order_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->restrictOnDelete();
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('variant_id');
            $table->string('product_name');
            $table->string('variant_name', 50)->nullable();
            $table->integer('quantity');
            $table->decimal('unit_price', 12, 2);
            $table->decimal('topping_amount', 12, 2)->default(0);
            $table->decimal('subtotal', 15, 2);
            $table->string('note', 500)->nullable();
            $table->string('status')->default('pending');
            $table->timestamps();
        });

        Schema::create('order_item_toppings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_item_id')->constrained('order_items')->restrictOnDelete();
            $table->unsignedBigInteger('topping_id');
            $table->string('topping_name');
            $table->decimal('price', 12, 2);
            $table->integer('quantity');
            $table->timestamps();
        });

        $this->withoutVite();
    }

    private function staff(string $role = 'staff', string $status = 'active'): User
    {
        return User::forceCreate([
            'name' => 'Cancellation Staff',
            'email' => uniqid('cancel-', true).'@example.test',
            'password' => 'test-only-password',
            'role' => $role,
            'status' => $status,
        ]);
    }

    private function table(string $status = 'available'): CafeTable
    {
        $area = Area::create(['name' => uniqid('Area-', true), 'status' => 'active']);

        return CafeTable::forceCreate([
            'area_id' => $area->id,
            'table_code' => strtoupper(uniqid('T', false)),
            'table_name' => 'Bàn hủy đơn',
            'capacity' => 4,
            'status' => $status,
        ]);
    }

    private function order(User $staff, string $status = 'pending', ?CafeTable $table = null): Order
    {
        return Order::forceCreate([
            'order_code' => strtoupper(uniqid('ORD-', true)),
            'table_id' => $table?->id,
            'user_id' => $staff->id,
            'order_type' => $table === null ? 'takeaway' : 'dine_in',
            'status' => $status,
            'subtotal' => '10.00',
            'discount_amount' => '0.00',
            'tax_amount' => '0.00',
            'total_amount' => '10.00',
            'ordered_at' => now(),
        ]);
    }

    private function item(Order $order, string $status = 'pending'): OrderItem
    {
        return OrderItem::forceCreate([
            'order_id' => $order->id,
            'product_id' => 1,
            'variant_id' => 1,
            'product_name' => 'Latte snapshot',
            'variant_name' => 'Lớn',
            'quantity' => 1,
            'unit_price' => '10.00',
            'topping_amount' => '0.00',
            'subtotal' => '10.00',
            'status' => $status,
        ]);
    }

    public function test_active_staff_can_cancel_an_empty_pending_order_without_deleting_it(): void
    {
        $staff = $this->staff();
        $order = $this->order($staff);

        $this->actingAs($staff, 'web')->patch(route('staff.orders.cancel', $order))
            ->assertRedirect(route('staff.orders.show', $order))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'cancelled']);
        $this->assertDatabaseCount('orders', 1);
    }

    public function test_cancel_preserves_pending_item_and_topping_snapshots(): void
    {
        $staff = $this->staff();
        $order = $this->order($staff);
        $item = $this->item($order);
        $topping = OrderItemTopping::forceCreate([
            'order_item_id' => $item->id,
            'topping_id' => 1,
            'topping_name' => 'Kem sữa snapshot',
            'price' => '2.00',
            'quantity' => 1,
        ]);

        $this->actingAs($staff, 'web')->patch(route('staff.orders.cancel', $order))
            ->assertRedirect(route('staff.orders.show', $order));

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'cancelled']);
        $this->assertDatabaseHas('order_items', ['id' => $item->id, 'status' => 'pending']);
        $this->assertDatabaseHas('order_item_toppings', ['id' => $topping->id, 'topping_name' => 'Kem sữa snapshot']);
    }

    public function test_cancelling_dine_in_releases_an_occupied_table_when_no_other_pending_order_exists(): void
    {
        $staff = $this->staff();
        $table = $this->table('occupied');
        $order = $this->order($staff, 'pending', $table);

        $this->actingAs($staff, 'web')->patch(route('staff.orders.cancel', $order))
            ->assertRedirect(route('staff.orders.show', $order));

        $this->assertSame('cancelled', $order->fresh()->status);
        $this->assertSame('available', $table->fresh()->status);
    }

    public function test_cancelling_dine_in_keeps_table_occupied_when_another_pending_order_exists(): void
    {
        $staff = $this->staff();
        $table = $this->table('occupied');
        $order = $this->order($staff, 'pending', $table);
        $this->order($staff, 'pending', $table);

        $this->actingAs($staff, 'web')->patch(route('staff.orders.cancel', $order))
            ->assertRedirect(route('staff.orders.show', $order));

        $this->assertSame('cancelled', $order->fresh()->status);
        $this->assertSame('occupied', $table->fresh()->status);
    }

    public function test_cancelling_takeaway_does_not_change_any_table(): void
    {
        $staff = $this->staff();
        $unrelatedTable = $this->table('maintenance');
        $order = $this->order($staff);

        $this->actingAs($staff, 'web')->patch(route('staff.orders.cancel', $order))
            ->assertRedirect(route('staff.orders.show', $order));

        $this->assertSame('cancelled', $order->fresh()->status);
        $this->assertSame('maintenance', $unrelatedTable->fresh()->status);
    }

    public function test_cancelling_dine_in_does_not_change_a_maintenance_table(): void
    {
        $staff = $this->staff();
        $table = $this->table('maintenance');
        $order = $this->order($staff, 'pending', $table);

        $this->actingAs($staff, 'web')->patch(route('staff.orders.cancel', $order))
            ->assertRedirect(route('staff.orders.show', $order));

        $this->assertSame('cancelled', $order->fresh()->status);
        $this->assertSame('maintenance', $table->fresh()->status);
    }

    public function test_completed_or_already_cancelled_orders_cannot_be_cancelled(): void
    {
        $staff = $this->staff();

        foreach (['completed', 'cancelled'] as $status) {
            $order = $this->order($staff, $status);
            $this->actingAs($staff, 'web')->patchJson(route('staff.orders.cancel', $order))
                ->assertUnprocessable()
                ->assertJsonValidationErrors('order');
            $this->assertSame($status, $order->fresh()->status);
        }
    }

    public function test_order_with_non_pending_items_cannot_be_cancelled_or_release_its_table(): void
    {
        $staff = $this->staff();

        foreach (['preparing', 'ready', 'served', 'cancelled'] as $status) {
            $table = $this->table('occupied');
            $order = $this->order($staff, 'pending', $table);
            $this->item($order, $status);

            $this->actingAs($staff, 'web')->patchJson(route('staff.orders.cancel', $order))
                ->assertUnprocessable()
                ->assertJsonValidationErrors('order_item');

            $this->assertSame('pending', $order->fresh()->status);
            $this->assertSame('occupied', $table->fresh()->status);
        }
    }

    public function test_guests_and_inactive_users_cannot_cancel_orders(): void
    {
        $staff = $this->staff();
        $order = $this->order($staff);
        $route = route('staff.orders.cancel', $order);

        $this->patch($route)->assertRedirect(route('login'));
        $this->actingAs($this->staff('admin', 'inactive'), 'web')->patch($route)->assertForbidden();
        $this->actingAs($this->staff('staff', 'inactive'), 'web')->patch($route)->assertForbidden();
        $this->assertSame('pending', $order->fresh()->status);
    }

    public function test_client_cannot_override_sensitive_order_or_table_fields_during_cancellation(): void
    {
        $staff = $this->staff();
        $table = $this->table('occupied');
        $otherTable = $this->table('maintenance');
        $order = $this->order($staff, 'pending', $table);

        $this->actingAs($staff, 'web')->patch(route('staff.orders.cancel', $order), [
            'status' => 'completed',
            'table_id' => $otherTable->id,
            'user_id' => 999,
            'total_amount' => '999.99',
        ])->assertRedirect(route('staff.orders.show', $order));

        $freshOrder = $order->fresh();
        $this->assertSame('cancelled', $freshOrder->status);
        $this->assertSame($table->id, $freshOrder->table_id);
        $this->assertSame($staff->id, $freshOrder->user_id);
        $this->assertSame('10.00', $freshOrder->total_amount);
        $this->assertSame('available', $table->fresh()->status);
        $this->assertSame('maintenance', $otherTable->fresh()->status);
    }

    public function test_cancellation_rolls_back_when_the_order_save_fails(): void
    {
        $staff = $this->staff();
        $table = $this->table('occupied');
        $order = $this->order($staff, 'pending', $table);
        Order::saving(fn () => throw new QueryException('sqlite', 'private SQL', [], new \RuntimeException('private failure')));

        try {
            $this->actingAs($staff, 'web')->from(route('staff.orders.show', $order))
                ->patch(route('staff.orders.cancel', $order))
                ->assertRedirect(route('staff.orders.show', $order))
                ->assertSessionHasErrors('order');
            $this->assertSame('pending', $order->fresh()->status);
            $this->assertSame('occupied', $table->fresh()->status);
        } finally {
            Order::flushEventListeners();
        }
    }

    public function test_cancellation_rolls_back_when_releasing_the_table_fails(): void
    {
        $staff = $this->staff();
        $table = $this->table('occupied');
        $order = $this->order($staff, 'pending', $table);
        CafeTable::saving(fn (CafeTable $saving) => $saving->isDirty('status')
            ? throw new QueryException('sqlite', 'private SQL', [], new \RuntimeException('private failure'))
            : null);

        try {
            $this->actingAs($staff, 'web')->from(route('staff.orders.show', $order))
                ->patch(route('staff.orders.cancel', $order))
                ->assertRedirect(route('staff.orders.show', $order))
                ->assertSessionHasErrors('order');
            $this->assertSame('pending', $order->fresh()->status);
            $this->assertSame('occupied', $table->fresh()->status);
        } finally {
            CafeTable::flushEventListeners();
        }
    }
}
