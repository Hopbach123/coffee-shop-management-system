<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\CafeTable;
use App\Models\Customer;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ReservationIntegrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame('sqlite', DB::connection()->getDriverName());
        $this->assertSame(':memory:', DB::connection()->getDatabaseName());
        foreach (['0001_01_01_000000_create_users_table.php', '2026_08_14_000001_create_customers_table.php', '2026_08_14_000002_create_areas_table.php', '2026_08_14_000006_create_cafe_tables_table.php'] as $migration) {
            (require database_path('migrations/'.$migration))->up();
        }
        // Existing reservations schema fixture; MySQL CHECK ALTER is not portable to SQLite.
        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('table_id')->nullable()->constrained('cafe_tables')->restrictOnDelete();
            $table->string('customer_name');
            $table->string('customer_phone', 20);
            $table->integer('guest_count');
            $table->dateTime('reservation_start_at');
            $table->dateTime('reservation_end_at');
            $table->text('note')->nullable();
            $table->enum('status', ['pending', 'confirmed', 'arrived', 'completed', 'cancelled', 'no_show'])->default('pending');
            $table->foreignId('created_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });
        $this->withoutVite();
        $this->travelTo(now()->setDate(2026, 10, 19)->setTime(10, 0));
    }

    private function orderFixture(): void
    {
        // Subset of the existing orders schema needed for read-only relationship tests.
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reservation_id')->nullable()->constrained('reservations')->restrictOnDelete();
            $table->enum('status', ['pending', 'completed', 'cancelled'])->default('pending');
        });
    }

    public function test_create_approve_cancel_rebook_and_order_guard_preserve_master_records(): void
    {
        $this->orderFixture();
        $admin = User::forceCreate(['name' => 'Admin', 'email' => 'integration@example.test', 'password' => 'test-only-password', 'role' => 'admin', 'status' => 'active']);
        $this->actingAs($admin, 'web');
        $area = Area::create(['name' => 'Integration floor']);
        $table = CafeTable::create(['area_id' => $area->id, 'table_code' => 'INT1', 'capacity' => 4]);
        $customer = Customer::create(['name' => 'Profile name', 'phone' => '0900000000']);
        $masters = [$area->fresh()->getAttributes(), $table->fresh()->getAttributes(), $customer->fresh()->getAttributes()];
        $payload = ['customer_id' => $customer->id, 'table_id' => $table->id, 'customer_name' => 'Booking snapshot', 'customer_phone' => '0901111111',
            'guest_count' => 4, 'reservation_start_at' => '2026-10-20T12:00', 'reservation_end_at' => '2026-10-20T13:00', 'note' => 'Keep history'];
        $this->get('/admin/reservations/create')->assertOk()->assertSee('Integration floor');
        $this->post('/admin/reservations', $payload)->assertSessionHasNoErrors()->assertRedirect();
        $first = Reservation::sole();
        $this->assertSame('pending', $first->status);
        $this->assertSame($admin->id, $first->created_by);
        $this->get('/admin/reservations?status=pending&search=Booking')->assertOk()->assertSee('Booking snapshot');
        $this->patch(route('admin.reservations.approve', $first))->assertSessionHasNoErrors();
        $this->assertSame('confirmed', $first->fresh()->status);
        $this->postJson('/admin/reservations', $payload)->assertUnprocessable()->assertJsonValidationErrors('table_id');
        $this->patch(route('admin.reservations.cancel', $first), ['expected_status' => 'confirmed'])->assertSessionHasNoErrors();
        $this->assertSame('cancelled', $first->fresh()->status);
        $this->post('/admin/reservations', $payload)->assertSessionHasNoErrors();
        $second = Reservation::orderByDesc('id')->firstOrFail();
        $this->assertNotSame($first->id, $second->id);
        $this->patch(route('admin.reservations.approve', $second))->assertSessionHasNoErrors();
        DB::table('orders')->insert(['reservation_id' => $second->id, 'status' => 'pending']);
        $this->patchJson(route('admin.reservations.cancel', $second), ['expected_status' => 'confirmed'])->assertUnprocessable()->assertJsonValidationErrors('cancellation');
        $this->assertSame('confirmed', $second->fresh()->status);
        $this->assertDatabaseCount('reservations', 2);
        $this->assertDatabaseHas('orders', ['reservation_id' => $second->id, 'status' => 'pending']);
        foreach ([$first, $second] as $reservation) {
            $this->get(route('admin.reservations.show', $reservation))->assertOk()->assertSee('Booking snapshot')->assertSee('Keep history')->assertSee('Integration floor');
        }
        $this->assertSame($masters, [$area->fresh()->getAttributes(), $table->fresh()->getAttributes(), $customer->fresh()->getAttributes()]);
    }

    public function test_route_matrix_has_only_protected_expected_actions_and_no_get_writes(): void
    {
        $routes = collect(Route::getRoutes()->getRoutes())->filter(fn ($route) => str_starts_with($route->uri(), 'admin/reservations'));
        $this->assertCount(6, $routes);
        $expected = ['admin.reservations.index' => 'GET', 'admin.reservations.create' => 'GET', 'admin.reservations.show' => 'GET',
            'admin.reservations.store' => 'POST', 'admin.reservations.approve' => 'PATCH', 'admin.reservations.cancel' => 'PATCH'];
        foreach ($routes as $route) {
            $this->assertArrayHasKey($route->getName(), $expected);
            $this->assertContains($expected[$route->getName()], $route->methods());
            $this->assertContains('auth:web', $route->gatherMiddleware());
            $this->assertContains('role:admin', $route->gatherMiddleware());
        }
    }

    public function test_post_write_failures_roll_back_create_approve_and_cancel(): void
    {
        $this->orderFixture();
        $admin = User::forceCreate(['name' => 'Admin', 'email' => 'rollback@example.test', 'password' => 'test-only-password', 'role' => 'admin', 'status' => 'active']);
        $this->actingAs($admin, 'web');
        $area = Area::create(['name' => 'Rollback floor']);
        $table = CafeTable::create(['area_id' => $area->id, 'table_code' => 'RB', 'capacity' => 4]);
        $payload = ['table_id' => $table->id, 'customer_name' => 'Guest', 'customer_phone' => '0900000000', 'guest_count' => 2,
            'reservation_start_at' => '2026-10-20T12:00', 'reservation_end_at' => '2026-10-20T13:00'];
        $reservation = Reservation::create($payload);
        $before = $reservation->fresh()->getAttributes();
        Reservation::saved(fn () => throw new QueryException('sqlite', 'private SQL', [], new \RuntimeException('failure after SQL write')));
        try {
            $createPayload = array_merge($payload, ['reservation_start_at' => '2026-10-20T14:00', 'reservation_end_at' => '2026-10-20T15:00']);
            $this->from('/admin/reservations/create')->post('/admin/reservations', $createPayload)->assertSessionHasErrors('reservation')->assertSessionMissing('success');
            $this->assertDatabaseCount('reservations', 1);
            $this->patch(route('admin.reservations.approve', $reservation))->assertSessionHasErrors('approval')->assertSessionMissing('success');
            $this->assertSame($before, $reservation->fresh()->getAttributes());
            $this->patch(route('admin.reservations.cancel', $reservation), ['expected_status' => 'pending'])->assertSessionHasErrors('cancellation')->assertSessionMissing('success');
            $this->assertSame($before, $reservation->fresh()->getAttributes());
        } finally {
            Reservation::flushEventListeners();
        }
    }
}
