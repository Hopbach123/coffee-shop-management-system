<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\CafeTable;
use App\Models\Customer;
use App\Models\Reservation;
use App\Models\User;
use App\Services\ReservationAvailabilityService;
use Carbon\CarbonImmutable;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ReservationAvailabilityTest extends TestCase
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

    private function table(): CafeTable
    {
        $user = User::forceCreate(['name' => 'Admin', 'email' => 'admin@example.test', 'password' => 'test-only-password', 'role' => 'admin', 'status' => 'active']);
        $this->actingAs($user, 'web');
        $area = Area::create(['name' => 'Floor']);

        return CafeTable::create(['area_id' => $area->id, 'table_code' => 'T1', 'capacity' => 4]);
    }

    private function payload(CafeTable $table, string $start = '12:00', string $end = '14:00'): array
    {
        return ['table_id' => $table->id, 'customer_name' => 'New guest', 'customer_phone' => '0901234567', 'guest_count' => 2,
            'reservation_start_at' => '2026-10-20T'.$start, 'reservation_end_at' => '2026-10-20T'.$end];
    }

    private function existing(CafeTable $table, string $status = 'pending'): Reservation
    {
        return Reservation::forceCreate(['table_id' => $table->id, 'customer_name' => 'Private previous guest', 'customer_phone' => 'private-phone',
            'guest_count' => 2, 'reservation_start_at' => '2026-10-20 12:00:00', 'reservation_end_at' => '2026-10-20 14:00:00', 'status' => $status]);
    }

    public function test_all_overlap_shapes_are_rejected_without_mutating_history_or_leaking_contacts(): void
    {
        $table = $this->table();
        $existing = $this->existing($table);
        $before = $existing->fresh()->getAttributes();
        foreach ([['12:00', '14:00'], ['13:00', '15:00'], ['11:00', '13:00'], ['11:00', '15:00'], ['12:30', '13:30']] as [$start, $end]) {
            $response = $this->postJson('/admin/reservations', $this->payload($table, $start, $end));
            $response->assertUnprocessable()->assertJsonValidationErrors('table_id')->assertDontSee('Private previous guest')->assertDontSee('private-phone');
        }
        $this->assertDatabaseCount('reservations', 1);
        $this->assertSame($before, $existing->fresh()->getAttributes());
        $this->assertSame('available', $table->fresh()->status);
    }

    public function test_status_policy_includes_pending_and_excludes_terminal_states(): void
    {
        $table = $this->table();
        $existing = $this->existing($table);
        foreach (['pending', 'confirmed', 'arrived', 'completed', 'cancelled', 'no_show'] as $status) {
            $existing->forceFill(['status' => $status])->save();
            $response = $this->postJson('/admin/reservations', $this->payload($table));
            if (in_array($status, ['pending', 'confirmed', 'arrived'], true)) {
                $response->assertUnprocessable()->assertJsonValidationErrors('table_id');
            } else {
                $response->assertRedirect();
                // Keep history while freeing this accepted request for the next case.
                Reservation::where('id', '!=', $existing->id)->update(['status' => 'cancelled']);
            }
        }
        $this->assertDatabaseCount('reservations', 4);
    }

    public function test_adjacent_and_separate_intervals_and_other_tables_are_allowed(): void
    {
        $table = $this->table();
        $this->existing($table);
        foreach ([['10:00', '11:00'], ['11:00', '12:00'], ['14:00', '15:00'], ['16:00', '17:00']] as [$start, $end]) {
            $this->post('/admin/reservations', $this->payload($table, $start, $end))->assertSessionHasNoErrors()->assertRedirect();
        }
        $other = CafeTable::create(['area_id' => $table->area_id, 'table_code' => 'T2', 'capacity' => 4]);
        $this->post('/admin/reservations', $this->payload($other))->assertSessionHasNoErrors()->assertRedirect();
        $this->assertDatabaseCount('reservations', 6);
    }

    public function test_conflict_redirect_preserves_form_values_and_renders_message(): void
    {
        $table = $this->table();
        $this->existing($table);
        $this->from('/admin/reservations/create')->followingRedirects()->post('/admin/reservations', $this->payload($table))
            ->assertOk()->assertSee('Bàn đã có lịch đặt trùng thời gian.')->assertSee('New guest')->assertSee('0901234567')
            ->assertSee('2026-10-20T12:00')->assertSee('aria-invalid="true"', false);
        $this->assertDatabaseCount('reservations', 1);
    }

    public function test_checker_requires_transaction_and_can_exclude_its_own_approval_context(): void
    {
        $table = $this->table();
        $existing = $this->existing($table);
        $start = CarbonImmutable::parse('2026-10-20 12:00:00', 'UTC');
        $end = $start->addHours(2);
        $service = app(ReservationAvailabilityService::class);
        DB::transaction(function () use ($service, $table, $existing, $start, $end) {
            $this->assertSame($table->id, $service->assertAvailable($table->id, 4, $start, $end, $existing)->id);
        });
        $this->assertDatabaseCount('reservations', 1);
        $this->expectException(\LogicException::class);
        $service->assertAvailable($table->id, 2, $start, $end);
    }

    public function test_create_rejects_start_that_expires_while_waiting_for_table_lock(): void
    {
        $table = $this->table();
        $checker = new class(fn () => $this->travel(2)->minutes()) extends ReservationAvailabilityService
        {
            public function __construct(private \Closure $afterLock) {}

            public function lockTable(int $tableId): CafeTable
            {
                $table = parent::lockTable($tableId);
                ($this->afterLock)();

                return $table;
            }
        };
        $this->app->instance(ReservationAvailabilityService::class, $checker);
        $payload = $this->payload($table);
        $payload['reservation_start_at'] = '2026-10-19T10:01';
        $payload['reservation_end_at'] = '2026-10-19T11:00';
        $this->postJson('/admin/reservations', $payload)->assertUnprocessable()->assertJsonValidationErrors('reservation_start_at');
        $this->assertDatabaseCount('reservations', 0);
    }

    public function test_create_rechecks_future_start_after_waiting_for_customer(): void
    {
        $table = $this->table();
        $customer = Customer::create(['name' => 'Linked guest']);
        Customer::retrieved(fn () => $this->travel(2)->minutes());
        try {
            $payload = $this->payload($table);
            $payload['customer_id'] = $customer->id;
            $payload['reservation_start_at'] = '2026-10-19T10:01';
            $payload['reservation_end_at'] = '2026-10-19T11:00';
            $this->postJson('/admin/reservations', $payload)->assertUnprocessable()->assertJsonValidationErrors('reservation_start_at');
            $this->assertDatabaseCount('reservations', 0);
        } finally {
            Customer::flushEventListeners();
        }
    }
}
