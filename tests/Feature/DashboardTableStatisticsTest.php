<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\CafeTable;
use App\Models\User;
use App\Services\Dashboard\TableStatisticsService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DashboardTableStatisticsTest extends TestCase
{
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame('sqlite', DB::connection()->getDriverName());
        $this->assertSame(':memory:', DB::connection()->getDatabaseName());
        foreach (['0001_01_01_000000_create_users_table.php', '2026_08_14_000001_create_customers_table.php', '2026_08_14_000002_create_areas_table.php', '2026_08_14_000006_create_cafe_tables_table.php'] as $migration) {
            (require database_path('migrations/'.$migration))->up();
        }
        // Existing Order columns needed for aggregate tests; MySQL CHECK ALTER is not SQLite-compatible.
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_code', 50)->unique();
            $table->foreignId('table_id')->nullable()->constrained('cafe_tables')->restrictOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->restrictOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->unsignedBigInteger('reservation_id')->nullable();
            $table->enum('order_type', ['dine_in', 'takeaway'])->default('dine_in');
            $table->enum('status', ['pending', 'completed', 'cancelled'])->default('pending');
            $table->dateTime('ordered_at');
            $table->dateTime('completed_at')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
        });
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders');
            $table->decimal('amount', 15, 2);
            $table->enum('status', ['pending', 'paid', 'failed', 'refunded'])->default('pending');
        });
        $this->admin = User::forceCreate(['name' => 'Admin', 'email' => 'dashboard@example.test', 'password' => 'test-only-password', 'role' => 'admin', 'status' => 'active']);
        $this->withoutVite();
    }

    public function test_empty_table_kpi_and_all_three_dashboard_groups_render(): void
    {
        $expected = ['total' => 0, 'by_status' => ['available' => 0, 'occupied' => 0, 'maintenance' => 0], 'unclassified' => 0];
        $this->assertSame($expected, app(TableStatisticsService::class)->summarize());
        $this->actingAs($this->admin, 'web')->get('/admin/dashboard')->assertOk()->assertViewHas('tableStatistics', $expected)
            ->assertSee('Tổng đơn hàng')->assertSee('Tổng thanh toán thành công')->assertSee('Tổng số bàn')
            ->assertSee('Chưa có bàn được ghi nhận.')->assertSee('Bàn trống')->assertSee('Đang có khách')->assertSee('Bảo trì')
            ->assertDontSee('Đã đặt')->assertDontSee('NaN')->assertDontSee('Infinity');
    }

    public function test_counts_all_current_statuses_excludes_only_deleted_tables_and_refreshes_each_request(): void
    {
        $first = Area::create(['name' => 'Main']);
        $second = Area::forceCreate(['name' => 'Inactive', 'status' => 'inactive']);
        $deletedArea = Area::create(['name' => 'Deleted area']);
        $rows = [
            ['area_id' => $first->id, 'table_code' => 'A', 'status' => 'available'],
            ['area_id' => $first->id, 'table_code' => 'B', 'status' => 'occupied'],
            ['area_id' => $second->id, 'table_code' => 'C', 'status' => 'maintenance'],
            ['area_id' => $deletedArea->id, 'table_code' => 'D', 'status' => 'available'],
        ];
        foreach ($rows as $row) {
            CafeTable::forceCreate($row + ['capacity' => 4]);
        }
        $deletedArea->delete();
        CafeTable::create(['area_id' => $first->id, 'table_code' => 'DELETED', 'capacity' => 4])->delete();
        $this->assertSame(['total' => 4, 'by_status' => ['available' => 2, 'occupied' => 1, 'maintenance' => 1], 'unclassified' => 0], app(TableStatisticsService::class)->summarize());
        $this->actingAs($this->admin, 'web')->get('/admin/dashboard')->assertOk()->assertViewHas('tableStatistics', fn ($stats) => $stats['total'] === 4);
        DB::table('cafe_tables')->where('table_code', 'A')->update(['status' => 'occupied']);
        $this->get('/admin/dashboard')->assertOk()->assertViewHas('tableStatistics', fn ($stats) => $stats['by_status']['occupied'] === 2 && array_sum($stats['by_status']) === $stats['total']);
    }

    public function test_table_order_and_paid_kpis_integrate_without_multiplying_table_count(): void
    {
        $area = Area::create(['name' => 'Floor']);
        $table = CafeTable::create(['area_id' => $area->id, 'table_code' => 'INT', 'capacity' => 4]);
        $ids = [];
        foreach (['pending', 'completed', 'cancelled'] as $i => $status) {
            $ids[] = DB::table('orders')->insertGetId(['order_code' => 'O-'.$i, 'table_id' => $table->id, 'user_id' => $this->admin->id,
                'status' => $status, 'ordered_at' => '2026-10-19 12:00:00']);
        }
        DB::table('payments')->insert(['order_id' => $ids[1], 'amount' => '25000.50', 'status' => 'paid']);
        $this->actingAs($this->admin, 'web')->get('/admin/dashboard')->assertOk()->assertSee('25.000,50 VNĐ')
            ->assertViewHas('orderStatistics', fn ($stats) => $stats['total'] === 3)
            ->assertViewHas('tableStatistics', fn ($stats) => $stats['total'] === 1);
        DB::table('payments')->insert(['order_id' => $ids[1], 'amount' => '25000.50', 'status' => 'paid']);
        $this->get('/admin/dashboard')->assertOk()->assertSee('Chưa xác định — cần kiểm tra dữ liệu thanh toán.')
            ->assertSee('Tổng số bàn')->assertViewHas('tableStatistics', fn ($stats) => $stats['total'] === 1);
    }

    public function test_three_aggregate_queries_are_read_only_without_table_hydration_or_related_joins(): void
    {
        $area = Area::create(['name' => 'Floor']);
        CafeTable::create(['area_id' => $area->id, 'table_code' => 'R1', 'capacity' => 4]);
        $before = [DB::table('cafe_tables')->get()->toJson(), DB::table('areas')->get()->toJson()];
        $this->actingAs($this->admin, 'web');
        CafeTable::retrieved(fn () => $this->fail('Table aggregate must not hydrate CafeTable models.'));
        try {
            DB::enableQueryLog();
            $this->get('/admin/dashboard')->assertOk();
            $queries = DB::getQueryLog();
            DB::disableQueryLog();
            DB::flushQueryLog();
            $this->assertCount(3, $queries);
            $tableQueries = [];
            foreach ($queries as $query) {
                $this->assertDoesNotMatchRegularExpression('/^\s*(insert|update|delete|replace|alter|drop)\b/i', $query['query']);
                $this->assertDoesNotMatchRegularExpression('/\b(join|reservations|order_items|areas)\b/i', $query['query']);
                if (str_contains($query['query'], '"cafe_tables"')) {
                    $tableQueries[] = $query['query'];
                }
            }
            $this->assertCount(1, $tableQueries);
            $this->assertStringContainsString('COUNT(*)', $tableQueries[0]);
            $this->assertStringContainsString('group by', $tableQueries[0]);
            $this->assertStringContainsString('"deleted_at" is null', $tableQueries[0]);
            $this->assertSame($before, [DB::table('cafe_tables')->get()->toJson(), DB::table('areas')->get()->toJson()]);
        } finally {
            CafeTable::flushEventListeners();
        }
    }

    public function test_corrupted_status_is_counted_as_unclassified_without_exposing_its_raw_value(): void
    {
        $area = Area::create(['name' => 'Floor']);
        // Only in guarded in-memory SQLite: simulate a database violating its enum CHECK.
        DB::statement('PRAGMA ignore_check_constraints = ON');
        try {
            DB::table('cafe_tables')->insert(['area_id' => $area->id, 'table_code' => 'BAD', 'capacity' => 4, 'status' => '<script>bad</script>']);
        } finally {
            DB::statement('PRAGMA ignore_check_constraints = OFF');
        }
        $stats = app(TableStatisticsService::class)->summarize();
        $this->assertSame(1, $stats['total']);
        $this->assertSame(1, $stats['unclassified']);
        $this->assertSame($stats['total'], array_sum($stats['by_status']) + $stats['unclassified']);
        $this->actingAs($this->admin, 'web')->get('/admin/dashboard')->assertOk()->assertSee('Chưa phân loại — cần kiểm tra dữ liệu')->assertDontSee('<script>bad</script>', false);
        $this->assertDatabaseHas('cafe_tables', ['table_code' => 'BAD', 'status' => '<script>bad</script>']);
    }

    public function test_dashboard_table_data_remains_admin_only(): void
    {
        $this->get('/admin/dashboard')->assertRedirect(route('login'));
        $this->admin->forceFill(['role' => 'staff'])->save();
        $this->actingAs($this->admin, 'web')->get('/admin/dashboard')->assertForbidden();
        $this->admin->forceFill(['role' => 'admin'])->save();
        $this->actingAs($this->admin, 'web')->get('/admin/dashboard')->assertOk()->assertSee('Tổng số bàn');
    }
}
