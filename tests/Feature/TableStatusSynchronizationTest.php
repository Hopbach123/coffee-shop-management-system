<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\CafeTable;
use App\Models\User;
use App\Services\TableStatusReadService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\CreatesTableStatusSources;
use Tests\TestCase;

class TableStatusSynchronizationTest extends TestCase
{
    use CreatesTableStatusSources;

    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame('sqlite', DB::connection()->getDriverName());
        $this->assertSame(':memory:', DB::connection()->getDatabaseName());
        foreach (['0001_01_01_000000_create_users_table.php', '2026_08_14_000002_create_areas_table.php', '2026_08_14_000006_create_cafe_tables_table.php'] as $migration) {
            (require database_path('migrations/'.$migration))->up();
        }
        $this->prepareStatusReadTables();
        $this->withoutVite();
    }

    private function table(string $status = 'available', string $areaStatus = 'active'): CafeTable
    {
        $area = Area::forceCreate(['name' => 'Area', 'status' => $areaStatus]);

        return CafeTable::forceCreate(['area_id' => $area->id, 'table_code' => 'T'.($area->id), 'capacity' => 4, 'status' => $status]);
    }

    private function order(CafeTable $table, string $status = 'pending', string $type = 'dine_in'): int
    {
        return DB::table('orders')->insertGetId(['table_id' => $table->id, 'status' => $status, 'order_type' => $type]);
    }

    private function reservation(CafeTable $table, string $start, string $end, string $status = 'confirmed'): void
    {
        DB::table('reservations')->insert(['table_id' => $table->id, 'status' => $status, 'reservation_start_at' => $start, 'reservation_end_at' => $end]);
    }

    public function test_approved_matrix_and_payment_reference_never_change_persisted_status(): void
    {
        $expected = [];
        foreach ([['available', 0, 'available'], ['occupied', 1, 'occupied'], ['occupied', 0, 'conflict'], ['available', 1, 'conflict'], ['occupied', 2, 'conflict'], ['maintenance', 0, 'maintenance'], ['maintenance', 1, 'conflict']] as [$stored, $orders, $display]) {
            $table = $this->table($stored);
            for ($i = 0; $i < $orders; $i++) {
                $id = $this->order($table);
                DB::table('payments')->insert(['order_id' => $id, 'status' => 'paid']);
                foreach (['pending', 'failed', 'refunded'] as $paymentStatus) {
                    DB::table('payments')->insert(['order_id' => $id, 'status' => $paymentStatus]);
                }
            }
            $expected[$table->id] = $display;
        }
        $inactive = $this->table('occupied', 'inactive');
        $expected[$inactive->id] = 'unknown';
        $deleted = $this->table();
        $deleted->area->delete();
        $expected[$deleted->id] = 'unknown';
        $before = DB::table('cafe_tables')->get()->toJson();
        DB::enableQueryLog();
        $states = app(TableStatusReadService::class)->query(CarbonImmutable::parse('2026-10-20 12:00:00'))->pluck('display_status', 'id')->all();
        $this->assertSame($expected, $states);
        foreach (DB::getQueryLog() as $query) {
            $this->assertDoesNotMatchRegularExpression('/^\s*(insert|update|delete)\b/i', $query['query']);
        }
        DB::disableQueryLog();
        $this->assertSame($before, DB::table('cafe_tables')->get()->toJson());
    }

    public function test_reservation_half_open_boundaries_future_and_terminal_states(): void
    {
        $now = CarbonImmutable::parse('2026-10-20 12:00:00');
        foreach (['pending', 'confirmed', 'arrived'] as $status) {
            $table = $this->table();
            $this->reservation($table, '2026-10-20 12:00:00', '2026-10-20 13:00:00', $status);
            $reader = app(TableStatusReadService::class);
            $this->assertSame('reserved', $reader->query($now)->findOrFail($table->id)->display_status);
            $this->assertSame('available', $reader->query($now->addHour())->findOrFail($table->id)->display_status);
            $future = $reader->query($now->subSecond())->findOrFail($table->id);
            $this->assertSame('available', $future->display_status);
            $this->assertSame(1, (int) $future->future_reservation_count);
        }
        foreach (['completed', 'cancelled', 'no_show'] as $status) {
            $table = $this->table();
            $this->reservation($table, '2026-10-20 11:00:00', '2026-10-20 13:00:00', $status);
            $this->assertSame('available', app(TableStatusReadService::class)->query($now)->findOrFail($table->id)->display_status);
        }
        foreach (['occupied', 'maintenance'] as $status) {
            $table = $this->table($status);
            if ($status === 'occupied') {
                $this->order($table);
            }
            $this->reservation($table, '2026-10-20 11:00:00', '2026-10-20 13:00:00');
            $this->assertSame('conflict', app(TableStatusReadService::class)->query($now)->findOrFail($table->id)->display_status);
        }
    }

    public function test_shared_floor_detail_area_counts_refresh_and_terminal_orders(): void
    {
        $admin = User::forceCreate(['name' => 'Admin', 'email' => 'status@example.test', 'password' => 'test-only-password', 'role' => 'admin', 'status' => 'active']);
        $this->actingAs($admin, 'web');
        $table = $this->table('occupied');
        $order = $this->order($table);
        $this->get('/admin/tables?view=floor&status=occupied')->assertOk()->assertViewHas('summary', fn ($s) => (int) $s->occupied === 1 && (int) $s->total === 1)->assertSee('data-status-refresh', false);
        $this->get(route('admin.tables.show', $table->id))->assertOk()->assertViewHas('table', fn ($t) => $t->display_status === 'occupied');
        $this->get('/admin/areas')->assertOk()->assertViewHas('summary', fn ($s) => (int) $s->occupied_count === 1 && (int) $s->tables_count === 1);
        DB::table('orders')->where('id', $order)->update(['status' => 'completed']);
        $this->order($table, 'pending', 'takeaway');
        $this->get('/admin/tables?view=floor&status=conflict')->assertOk()->assertViewHas('summary', fn ($s) => (int) $s->conflict === 1);
        $this->get(route('admin.tables.show', $table->id))->assertOk()->assertViewHas('table', fn ($t) => $t->display_status === 'conflict')->assertDontSee('Tiếp tục gọi món');
        $this->get('/admin/areas')->assertOk()->assertViewHas('summary', fn ($s) => (int) $s->conflict_count === 1 && (int) $s->occupied_count === 0);
        $this->assertSame('occupied', $table->fresh()->status);
    }
}
