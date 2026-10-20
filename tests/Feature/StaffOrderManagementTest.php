<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\CafeTable;
use App\Models\Customer;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class StaffOrderManagementTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->assertSame('sqlite', config('database.default'));
        foreach ([
            '0001_01_01_000000_create_users_table.php',
            '2026_08_14_000001_create_customers_table.php',
            '2026_08_14_000002_create_areas_table.php',
            '2026_08_14_000006_create_cafe_tables_table.php',
        ] as $migration) {
            (require database_path('migrations/'.$migration))->up();
        }

        // Mirrors the orders migration without its MySQL-only ALTER TABLE CHECK statements.
        Schema::create('orders', function (Blueprint $table): void {
            $table->id();
            $table->string('order_code', 50)->unique();
            $table->foreignId('table_id')->nullable()->constrained('cafe_tables')->restrictOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->restrictOnDelete();
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

        $this->withoutVite();
    }

    private function staff(string $status = 'active'): User
    {
        return User::forceCreate([
            'name' => 'Staff',
            'email' => uniqid('staff-', true).'@example.test',
            'password' => 'test-only-password',
            'role' => 'staff',
            'status' => $status,
        ]);
    }

    private function table(string $status = 'available'): CafeTable
    {
        $area = Area::create(['name' => uniqid('Area-', true)]);

        return CafeTable::forceCreate([
            'area_id' => $area->id,
            'table_code' => strtoupper(uniqid('T', false)),
            'status' => $status,
        ]);
    }

    public function test_active_staff_can_create_takeaway_order_with_server_owned_fields(): void
    {
        $staff = $this->staff();
        $customer = Customer::create(['name' => 'Guest']);

        $this->actingAs($staff, 'web')->post('/staff/orders', [
            'order_type' => 'takeaway',
            'customer_id' => $customer->id,
            'note' => '  No sugar  ',
            'user_id' => 999,
            'status' => 'completed',
            'order_code' => 'FORGED',
            'subtotal' => '999.99',
            'discount_amount' => '1.00',
            'tax_amount' => '1.00',
            'total_amount' => '999.99',
        ])->assertRedirect(route('staff.orders.show', 1))->assertSessionHas('success');

        $order = Order::sole();
        $this->assertSame($staff->id, $order->user_id);
        $this->assertSame($customer->id, $order->customer_id);
        $this->assertNull($order->table_id);
        $this->assertSame('takeaway', $order->order_type);
        $this->assertSame('pending', $order->status);
        $this->assertNotSame('FORGED', $order->order_code);
        $this->assertSame('No sugar', $order->note);
        $this->assertSame('0.00', $order->subtotal);
        $this->assertSame('0.00', $order->discount_amount);
        $this->assertSame('0.00', $order->tax_amount);
        $this->assertSame('0.00', $order->total_amount);
        $this->assertNotNull($order->ordered_at);
    }

    public function test_active_staff_can_create_dine_in_order_and_occupy_table(): void
    {
        $staff = $this->staff();
        $table = $this->table();

        $this->actingAs($staff, 'web')->post('/staff/orders', ['order_type' => 'dine_in', 'table_id' => $table->id])
            ->assertRedirect(route('staff.orders.show', 1));

        $order = Order::sole();
        $this->assertSame($table->id, $order->table_id);
        $this->assertSame('pending', $order->status);
        $this->assertSame('occupied', $table->fresh()->status);
    }

    public function test_invalid_dine_in_tables_and_missing_table_are_rejected(): void
    {
        $staff = $this->staff();
        $this->actingAs($staff, 'web');

        $this->postJson('/staff/orders', ['order_type' => 'dine_in'])->assertUnprocessable()->assertJsonValidationErrors('table_id');
        $this->postJson('/staff/orders', ['order_type' => 'takeaway', 'table_id' => $this->table()->id])->assertUnprocessable()->assertJsonValidationErrors('table_id');

        foreach (['occupied', 'maintenance'] as $status) {
            $table = $this->table($status);
            $this->postJson('/staff/orders', ['order_type' => 'dine_in', 'table_id' => $table->id])
                ->assertUnprocessable()->assertJsonValidationErrors('table_id');
        }

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_second_pending_dine_in_order_for_the_same_table_is_rejected(): void
    {
        $staff = $this->staff();
        $table = $this->table();
        $this->actingAs($staff, 'web')->post('/staff/orders', ['order_type' => 'dine_in', 'table_id' => $table->id])->assertSessionHasNoErrors();

        // Simulate stale client data that still presents the table as available.
        $table->forceFill(['status' => 'available'])->save();
        $this->postJson('/staff/orders', ['order_type' => 'dine_in', 'table_id' => $table->id])
            ->assertUnprocessable()->assertJsonValidationErrors('table_id');

        $this->assertDatabaseCount('orders', 1);
    }

    public function test_takeaway_does_not_change_table_status(): void
    {
        $staff = $this->staff();
        $table = $this->table();

        $this->actingAs($staff, 'web')->post('/staff/orders', ['order_type' => 'takeaway'])->assertSessionHasNoErrors();

        $this->assertSame('available', $table->fresh()->status);
    }

    public function test_guest_admin_and_inactive_staff_cannot_access_staff_order_operations(): void
    {
        $table = $this->table();
        $this->get('/staff/orders')->assertRedirect(route('login'));
        $this->post('/staff/orders', ['order_type' => 'dine_in', 'table_id' => $table->id])->assertRedirect(route('login'));

        $admin = User::forceCreate(['name' => 'Admin', 'email' => uniqid('admin-', true).'@example.test', 'password' => 'test-only-password', 'role' => 'admin', 'status' => 'active']);
        $this->actingAs($admin, 'web')->get('/staff/orders')->assertForbidden();
        $this->post('/staff/orders', ['order_type' => 'dine_in', 'table_id' => $table->id])->assertForbidden();

        $inactiveStaff = $this->staff('inactive');
        $this->actingAs($inactiveStaff, 'web')->get('/staff/orders')->assertForbidden();
        $this->post('/staff/orders', ['order_type' => 'dine_in', 'table_id' => $table->id])->assertForbidden();
    }

    public function test_order_storage_failure_rolls_back_table_status_change(): void
    {
        $staff = $this->staff();
        $table = $this->table();
        Order::saving(fn () => throw new QueryException('sqlite', 'private SQL', [], new \RuntimeException('private failure')));

        try {
            $this->actingAs($staff, 'web')->from('/staff/orders/create')->followingRedirects()
                ->post('/staff/orders', ['order_type' => 'dine_in', 'table_id' => $table->id])
                ->assertOk()->assertSee('Không thể tạo đơn hàng.');
            $this->assertDatabaseCount('orders', 0);
            $this->assertSame('available', $table->fresh()->status);
        } finally {
            Order::flushEventListeners();
        }
    }
}
