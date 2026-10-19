<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\CafeTable;
use App\Models\Customer;
use App\Models\Reservation;
use App\Models\User;
use App\Services\ReservationCreationService;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ReservationCreationTest extends TestCase
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

    private function fixtures(): array
    {
        $admin = User::forceCreate(['name' => 'Admin', 'email' => 'admin@example.test', 'password' => 'test-only-password', 'role' => 'admin', 'status' => 'active']);
        $this->actingAs($admin, 'web');
        $area = Area::create(['name' => 'Floor']);
        $table = CafeTable::create(['area_id' => $area->id, 'table_code' => 'A01', 'capacity' => 4]);

        return [$admin, $area, $table];
    }

    private function payload(CafeTable $table): array
    {
        return ['customer_id' => null, 'table_id' => $table->id, 'customer_name' => '  Booking Guest  ', 'customer_phone' => '  0901234567  ',
            'guest_count' => 4, 'reservation_start_at' => '2026-10-20T12:00', 'reservation_end_at' => '2026-10-20T13:30', 'note' => null];
    }

    public function test_create_form_lists_only_eligible_profiles_tables_and_has_csrf_fields(): void
    {
        [, $area, $table] = $this->fixtures();
        $customer = Customer::create(['name' => '<b>Guest</b>', 'phone' => '0900000001']);
        Customer::forceCreate(['name' => 'Inactive profile', 'status' => 'inactive']);
        Customer::create(['name' => 'Deleted profile'])->delete();
        $occupied = CafeTable::forceCreate(['area_id' => $area->id, 'table_code' => 'OCC', 'capacity' => 6, 'status' => 'occupied']);
        CafeTable::forceCreate(['area_id' => $area->id, 'table_code' => 'MAINT', 'capacity' => 4, 'status' => 'maintenance']);
        CafeTable::create(['area_id' => $area->id, 'table_code' => 'DELETED', 'capacity' => 4])->delete();
        $inactive = Area::forceCreate(['name' => 'Inactive area', 'status' => 'inactive']);
        CafeTable::create(['area_id' => $inactive->id, 'table_code' => 'INACTIVE', 'capacity' => 4]);
        $deleted = Area::create(['name' => 'Deleted area']);
        CafeTable::create(['area_id' => $deleted->id, 'table_code' => 'DELETED-AREA', 'capacity' => 4]);
        $deleted->delete();
        $response = $this->get('/admin/reservations/create')->assertOk()->assertViewIs('admin.reservations.create')
            ->assertSee('name="_token"', false)->assertSee('Khách vãng lai')->assertSee('Chỉ dùng thử nghiệm')
            ->assertSee('&lt;b&gt;Guest&lt;/b&gt;', false)->assertDontSee('<b>Guest</b>', false)
            ->assertSee('Floor')->assertSee('4 khách')->assertDontSee('name="status"', false)->assertDontSee('name="created_by"', false);
        foreach (array_keys($this->payload($table)) as $field) {
            $response->assertSee('name="'.$field.'"', false);
        }
        $response->assertViewHas('customers', fn ($rows) => $rows->pluck('id')->all() === [$customer->id])
            ->assertViewHas('tables', fn ($rows) => $rows->pluck('id')->sort()->values()->all() === [$table->id, $occupied->id] && $rows->every(fn ($row) => $row->relationLoaded('area')));
    }

    public function test_guest_creation_whitelists_fields_and_writes_only_reservations(): void
    {
        [$admin, , $table] = $this->fixtures();
        $before = $table->fresh()->getAttributes();
        DB::enableQueryLog();
        $this->post('/admin/reservations', array_merge($this->payload($table), ['id' => 999, 'status' => 'confirmed', 'created_by' => 999, 'created_at' => '2000-01-01', 'updated_at' => '2000-01-01']))
            ->assertRedirect(route('admin.reservations.show', 1))->assertSessionHas('success', 'Đã tiếp nhận yêu cầu đặt bàn, đang chờ xác nhận');
        foreach (DB::getQueryLog() as $query) {
            if (preg_match('/^\s*(insert|update|delete|replace)\b/i', $query['query'])) {
                $this->assertMatchesRegularExpression('/^insert into "reservations" /i', $query['query']);
            }
        }
        DB::disableQueryLog();
        DB::flushQueryLog();
        $reservation = Reservation::sole();
        $this->assertSame('pending', $reservation->status);
        $this->assertSame($admin->id, $reservation->created_by);
        $this->assertSame('Booking Guest', $reservation->customer_name);
        $this->assertSame('0901234567', $reservation->customer_phone);
        $this->assertNull($reservation->customer_id);
        $this->assertNull($reservation->note);
        $this->assertSame('2026-10-20 12:00:00', $reservation->reservation_start_at->format('Y-m-d H:i:s'));
        $this->assertSame('2026-10-20 13:30:00', $reservation->reservation_end_at->format('Y-m-d H:i:s'));
        $this->assertNotSame(999, $reservation->id);
        $this->assertSame('2026-10-19', $reservation->created_at->format('Y-m-d'));
        $this->assertSame($before, $table->fresh()->getAttributes());
        $this->assertDatabaseCount('customers', 0);
        $this->get(route('admin.reservations.show', $reservation))->assertOk()->assertSee('Đã tiếp nhận yêu cầu đặt bàn, đang chờ xác nhận');
        $this->get('/admin/reservations?search=Booking')->assertOk()->assertSee('Booking Guest');
    }

    public function test_linked_customer_uses_editable_snapshot_and_occupied_table_does_not_change(): void
    {
        [, , $table] = $this->fixtures();
        $table->forceFill(['status' => 'occupied'])->save();
        $customer = Customer::create(['name' => 'Original', 'phone' => '0900000002']);
        $before = $customer->fresh()->getAttributes();
        $this->post('/admin/reservations', array_merge($this->payload($table), ['customer_id' => $customer->id, 'note' => '<b>Note</b>']))->assertSessionHasNoErrors();
        $reservation = Reservation::sole();
        $this->assertSame($customer->id, $reservation->customer_id);
        $this->assertSame('Booking Guest', $reservation->customer_name);
        $this->assertSame($before, $customer->fresh()->getAttributes());
        $this->assertSame('occupied', $table->fresh()->status);
        $this->get(route('admin.reservations.show', $reservation))->assertOk()->assertSee('&lt;b&gt;Note&lt;/b&gt;', false);
    }

    public function test_invalid_input_is_rejected_without_writes_and_old_values_are_retained(): void
    {
        [, , $table] = $this->fixtures();
        $cases = [
            'customer_id' => [999, 'bad', []], 'table_id' => [null, 999, 'bad', []],
            'customer_name' => ['', '   ', str_repeat('a', 256), ['bad']],
            'customer_phone' => ['', '   ', str_repeat('1', 21), 901234567, ['bad']],
            'guest_count' => [0, -1, 1.5, 5, 2147483648, null, ['bad']],
            'reservation_start_at' => ['2026-10-18T12:00', '2026-10-19T10:00', 'bad', '2026-02-30T12:00', null, []],
            'reservation_end_at' => ['2026-10-20T12:00', '2026-10-20T11:00', 'bad', null, []],
            'note' => [['bad'], str_repeat('a', 65536), str_repeat('ế', 22000)],
        ];
        foreach ($cases as $field => $values) {
            foreach ($values as $value) {
                $this->postJson('/admin/reservations', array_merge($this->payload($table), [$field => $value]))->assertUnprocessable()->assertJsonValidationErrors($field);
            }
        }
        $this->from('/admin/reservations/create')->followingRedirects()->post('/admin/reservations', array_merge($this->payload($table), ['guest_count' => 5, 'note' => 'Retained']))
            ->assertOk()->assertSee('Retained')->assertSee('Số khách không được vượt sức chứa bàn.')->assertSee('aria-invalid="true"', false);
        $this->assertDatabaseCount('reservations', 0);
    }

    public function test_ineligible_customer_table_and_area_are_rejected(): void
    {
        [, $area, $table] = $this->fixtures();
        $customer = Customer::forceCreate(['name' => 'Inactive', 'status' => 'inactive']);
        $this->postJson('/admin/reservations', array_merge($this->payload($table), ['customer_id' => $customer->id]))->assertUnprocessable()->assertJsonValidationErrors('customer_id');
        $customer->forceFill(['status' => 'active'])->save();
        $customer->delete();
        $this->postJson('/admin/reservations', array_merge($this->payload($table), ['customer_id' => $customer->id]))->assertUnprocessable()->assertJsonValidationErrors('customer_id');
        $table->forceFill(['status' => 'maintenance'])->save();
        $this->postJson('/admin/reservations', $this->payload($table))->assertUnprocessable()->assertJsonValidationErrors('table_id');
        $table->forceFill(['status' => 'available'])->save();
        $area->forceFill(['status' => 'inactive'])->save();
        $this->postJson('/admin/reservations', $this->payload($table))->assertUnprocessable()->assertJsonValidationErrors('table_id');
        $area->forceFill(['status' => 'active'])->save();
        $area->delete();
        $this->postJson('/admin/reservations', $this->payload($table))->assertUnprocessable()->assertJsonValidationErrors('table_id');
        $table->delete();
        $this->postJson('/admin/reservations', $this->payload($table))->assertUnprocessable()->assertJsonValidationErrors('table_id');
        $this->assertDatabaseCount('reservations', 0);
    }

    public function test_service_rechecks_validated_customer_and_capacity_after_changes(): void
    {
        [$admin, , $table] = $this->fixtures();
        $customer = Customer::create(['name' => 'Profile']);
        $payload = array_merge($this->payload($table), ['customer_id' => $customer->id]);
        $customer->forceFill(['status' => 'inactive'])->save();
        try {
            app(ReservationCreationService::class)->create($payload, $admin);
            $this->fail('Stale customer must be rejected.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('customer_id', $exception->errors());
        }
        $customer->forceFill(['status' => 'active'])->save();
        $table->forceFill(['capacity' => 2])->save();
        try {
            app(ReservationCreationService::class)->create($payload, $admin);
            $this->fail('Stale capacity must be rejected.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('guest_count', $exception->errors());
        }
        $this->assertDatabaseCount('reservations', 0);
    }

    public function test_storage_failure_rolls_back_preserves_input_and_hides_sql(): void
    {
        [, , $table] = $this->fixtures();
        Reservation::saving(fn () => throw new QueryException('sqlite', 'private SQL', [], new \RuntimeException('private failure')));
        try {
            $this->from('/admin/reservations/create')->followingRedirects()->post('/admin/reservations', $this->payload($table))
                ->assertOk()->assertSee('Không thể lưu yêu cầu đặt bàn.')->assertSee('0901234567')->assertDontSee('private SQL')->assertDontSee('private failure');
            $this->assertDatabaseCount('reservations', 0);
        } finally {
            Reservation::flushEventListeners();
        }
    }

    public function test_access_is_admin_only_and_creation_is_blocked_outside_local_testing(): void
    {
        [$admin, , $table] = $this->fixtures();
        $this->app['auth']->forgetGuards();
        $this->get('/admin/reservations/create')->assertRedirect(route('login'));
        $this->post('/admin/reservations', $this->payload($table))->assertRedirect(route('login'));
        $admin->forceFill(['role' => 'staff'])->save();
        $this->actingAs($admin, 'web')->get('/admin/reservations/create')->assertForbidden();
        $this->post('/admin/reservations', $this->payload($table))->assertForbidden();
        $admin->forceFill(['role' => 'admin'])->save();
        $this->actingAs($admin, 'web');
        foreach (['production', 'staging'] as $environment) {
            $this->app->instance('env', $environment);
            $this->get('/admin/reservations/create')->assertForbidden();
            $this->withSession(['_token' => 'test-csrf'])->post('/admin/reservations', array_merge($this->payload($table), ['_token' => 'test-csrf']))->assertForbidden();
            $this->get('/admin/reservations')->assertOk()->assertDontSee(route('admin.reservations.create'), false);
        }
        $this->app->instance('env', 'local');
        $this->get('/admin/reservations/create')->assertOk();
        $this->app->instance('env', 'testing');
        $this->assertDatabaseCount('reservations', 0);
    }
}
