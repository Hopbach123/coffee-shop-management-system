<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CustomerIntegrityTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame('sqlite', DB::connection()->getDriverName());
        $this->assertSame(':memory:', DB::connection()->getDatabaseName());
        foreach (['0001_01_01_000000_create_users_table.php', '2026_08_14_000001_create_customers_table.php'] as $migration) {
            (require database_path('migrations/'.$migration))->up();
        }
        // Minimal relational fixtures follow the existing service-test convention.
        // These do not represent or test the complete MySQL migrations/CHECK constraints.
        Schema::create('reservations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('customer_id')->nullable()->constrained()->restrictOnDelete()->noActionOnUpdate();
            $table->string('customer_name');
            $table->string('customer_phone', 20);
            $table->string('status');
            $table->timestamps();
        });
        Schema::create('orders', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('customer_id')->nullable()->constrained()->restrictOnDelete()->noActionOnUpdate();
            $table->foreignId('reservation_id')->nullable()->constrained()->restrictOnDelete()->noActionOnUpdate();
            $table->string('order_code', 50)->unique();
            $table->string('status');
            $table->decimal('total_amount', 15, 2);
            $table->timestamps();
        });
        $this->withoutVite();
    }

    private function fixtures(): array
    {
        $admin = User::forceCreate(['name' => 'Admin', 'email' => 'admin@example.test', 'password' => 'test-only-password', 'role' => 'admin', 'status' => 'active']);
        $customer = Customer::create(['name' => 'Original', 'phone' => '0901234567', 'email' => 'old@example.test']);
        $reservation = Reservation::forceCreate(['customer_id' => $customer->id, 'customer_name' => 'Historical name', 'customer_phone' => '0901234567', 'status' => 'completed']);
        $order = Order::forceCreate(['customer_id' => $customer->id, 'reservation_id' => $reservation->id, 'order_code' => 'TEST-HISTORY', 'status' => 'completed', 'total_amount' => '50000.00']);

        return [$admin, $customer, $reservation, $order];
    }

    public function test_profile_edit_and_status_changes_preserve_linked_history_and_relationships(): void
    {
        [$admin, $customer, $reservation, $order] = $this->fixtures();
        $originalReservation = $reservation->fresh()->getAttributes();
        $originalOrder = $order->fresh()->getAttributes();
        $this->actingAs($admin, 'web')->put('/admin/customers/'.$customer->id, [
            'name' => 'Updated', 'phone' => '0907654321', 'email' => 'new@example.test', 'birthday' => '2000-01-01',
            'customer_id' => 9999, 'reservation_id' => 9999, 'points' => 999, 'total_spent' => 999,
        ])->assertRedirect(route('admin.customers.index'))->assertSessionHas('success', 'Đã cập nhật khách hàng.');
        foreach (['inactive', 'active'] as $status) {
            $this->patch('/admin/customers/'.$customer->id.'/status', ['status' => $status])->assertRedirect(route('admin.customers.index'))
                ->assertSessionHas('success', $status === 'active' ? 'Đã kích hoạt hồ sơ khách hàng.' : 'Đã vô hiệu hóa hồ sơ khách hàng.');
            $this->assertSame($originalReservation, $reservation->fresh()->getAttributes());
            $this->assertSame($originalOrder, $order->fresh()->getAttributes());
            $this->assertSame([$order->id], $customer->orders()->pluck('id')->all());
            $this->assertSame([$reservation->id], $customer->reservations()->pluck('id')->all());
            $this->assertSame($customer->id, $order->fresh()->customer->id);
            $this->assertSame($customer->id, $reservation->fresh()->customer->id);
        }
        $this->assertSame('Historical name', $reservation->fresh()->customer_name);
        $this->assertSame('Updated', $customer->fresh()->name);
        $this->assertDatabaseCount('customers', 1);
        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseCount('reservations', 1);
    }

    public function test_creating_customer_does_not_create_or_modify_existing_history(): void
    {
        [$admin, $customer, $reservation, $order] = $this->fixtures();
        $originalReservation = $reservation->fresh()->getAttributes();
        $originalOrder = $order->fresh()->getAttributes();
        $this->actingAs($admin, 'web')->post('/admin/customers', ['name' => 'New Customer'])
            ->assertRedirect(route('admin.customers.index'));
        $this->assertSame($originalReservation, $reservation->fresh()->getAttributes());
        $this->assertSame($originalOrder, $order->fresh()->getAttributes());
        $this->assertDatabaseCount('customers', 2);
        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseCount('reservations', 1);
        $new = Customer::where('name', 'New Customer')->firstOrFail();
        $this->assertFalse($new->orders()->exists());
        $this->assertFalse($new->reservations()->exists());
        $this->assertSame('active', $new->status);
    }

    public function test_customer_routes_are_unique_admin_protected_and_delete_preserves_history(): void
    {
        $routes = collect(Route::getRoutes()->getRoutes())->filter(fn ($route) => str_starts_with($route->uri(), 'admin/customers'));
        $this->assertCount(7, $routes);
        $this->assertCount(7, $routes->map(fn ($route) => $route->getName())->unique());
        $this->assertCount(7, $routes->map(fn ($route) => implode('|', $route->methods()).' '.$route->uri())->unique());
        foreach ($routes as $route) {
            $this->assertContains('auth:web', $route->gatherMiddleware());
            $this->assertContains('role:admin', $route->gatherMiddleware());
        }
        [$admin, $customer, $reservation, $order] = $this->fixtures();
        $orderBefore = $order->fresh()->getAttributes();
        $reservationBefore = $reservation->fresh()->getAttributes();
        $this->actingAs($admin, 'web')->delete('/admin/customers/'.$customer->id)->assertRedirect(route('admin.customers.index'));
        $this->assertSoftDeleted('customers', ['id' => $customer->id]);
        $this->assertSame($orderBefore, $order->fresh()->getAttributes());
        $this->assertSame($reservationBefore, $reservation->fresh()->getAttributes());
        $this->assertTrue($order->fresh()->customer->trashed());
        $this->assertTrue($reservation->fresh()->customer->trashed());
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'customer_id' => $customer->id]);
        $this->assertDatabaseHas('reservations', ['id' => $reservation->id, 'customer_id' => $customer->id]);
    }

    public function test_listing_has_working_actions_and_delete_confirmation(): void
    {
        [$admin, $customer] = $this->fixtures();
        $this->actingAs($admin, 'web')->get('/admin/customers')->assertOk()
            ->assertSee(route('admin.customers.create'))->assertSee(route('admin.customers.edit', $customer))
            ->assertSee(route('admin.customers.status', $customer))->assertSee('Bạn có chắc muốn vô hiệu hóa hồ sơ khách hàng này?')
            ->assertSee('value="DELETE"', false)->assertSee('Bạn có chắc muốn xóa mềm hồ sơ khách hàng này?');
        $this->patch('/admin/customers/'.$customer->id.'/status', ['status' => 'inactive'])->assertRedirect(route('admin.customers.index'));
        $this->get('/admin/customers')->assertOk()->assertSee('Ngừng hoạt động')->assertSee('Bạn có chắc muốn kích hoạt hồ sơ khách hàng này?');
    }
}
