<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\CafeTable;
use App\Models\User;
use App\Services\Dashboard\OrderStatisticsService;
use App\Services\Dashboard\RevenueStatisticsService;
use App\Services\Dashboard\TableStatisticsService;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Tests\TestCase;

class DashboardIntegrationTest extends TestCase
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

    public function test_query_failures_return_safe_unavailable_response_even_when_debug_is_enabled(): void
    {
        config(['app.debug' => true]);
        $this->actingAs($this->admin, 'web');
        foreach ([OrderStatisticsService::class, RevenueStatisticsService::class, TableStatisticsService::class] as $service) {
            $mock = Mockery::mock($service);
            $mock->shouldReceive('summarize')->once()->andThrow(new QueryException('sqlite', 'PRIVATE_SQL_MARKER', ['PRIVATE_BINDING'], new \RuntimeException('PRIVATE_DRIVER_MARKER')));
            $this->app->instance($service, $mock);
            $this->get('/admin/dashboard')->assertStatus(503)->assertSee('Chưa thể tải số liệu Dashboard.')
                ->assertDontSee('PRIVATE_SQL_MARKER')->assertDontSee('PRIVATE_BINDING')->assertDontSee('PRIVATE_DRIVER_MARKER')
                ->assertDontSee('0,00 VNĐ');
            $this->app->forgetInstance($service);
        }
    }

    public function test_source_reconciliation_read_only_child_cardinality_and_three_query_timing(): void
    {
        $area = Area::create(['name' => 'Test floor']);
        $table = CafeTable::create(['area_id' => $area->id, 'table_code' => 'D1', 'capacity' => 4]);
        CafeTable::forceCreate(['area_id' => $area->id, 'table_code' => 'D2', 'capacity' => 4, 'status' => 'maintenance']);
        CafeTable::create(['area_id' => $area->id, 'table_code' => 'REMOVED', 'capacity' => 4])->delete();
        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('table_id')->constrained('cafe_tables');
            $table->string('status')->default('pending');
        });
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders');
        });
        $orderIds = [];
        foreach (['pending', 'completed', 'cancelled'] as $i => $status) {
            $orderIds[] = DB::table('orders')->insertGetId(['order_code' => 'RECON-'.$i, 'table_id' => $table->id, 'user_id' => $this->admin->id,
                'status' => $status, 'ordered_at' => '2026-10-19 12:00:00']);
        }
        foreach ($orderIds as $id) {
            DB::table('order_items')->insert([['order_id' => $id], ['order_id' => $id]]);
        }
        DB::table('reservations')->insert([['table_id' => $table->id], ['table_id' => $table->id]]);
        DB::table('payments')->insert([
            ['order_id' => $orderIds[1], 'amount' => '125.25', 'status' => 'paid'],
            ['order_id' => $orderIds[1], 'amount' => '125.25', 'status' => 'failed'],
            ['order_id' => $orderIds[1], 'amount' => '125.25', 'status' => 'pending'],
            ['order_id' => $orderIds[2], 'amount' => '200.25', 'status' => 'paid'],
        ]);
        $sources = ['orders', 'payments', 'cafe_tables', 'reservations', 'order_items', 'areas'];
        $before = [];
        foreach ($sources as $source) {
            $before[$source] = DB::table($source)->orderBy('id')->get()->toJson();
        }
        $expectedOrders = DB::table('orders')->count();
        $expectedTables = DB::table('cafe_tables')->whereNull('deleted_at')->count();
        $expectedPaid = (string) DB::table('payments')->where('status', 'paid')->sum('amount');
        [$whole, $fraction] = array_pad(explode('.', $expectedPaid), 2, '');
        $expectedPaid = $whole.'.'.str_pad($fraction, 2, '0');
        $this->actingAs($this->admin, 'web');
        DB::enableQueryLog();
        $response = $this->get('/admin/dashboard')->assertOk();
        $queries = DB::getQueryLog();
        DB::disableQueryLog();
        DB::flushQueryLog();
        $response->assertViewHas('orderStatistics', fn ($data) => $data['total'] === $expectedOrders && array_sum($data['by_status']) === $expectedOrders)
            ->assertViewHas('tableStatistics', fn ($data) => $data['total'] === $expectedTables && array_sum($data['by_status']) + $data['unclassified'] === $expectedTables)
            ->assertViewHas('revenueStatistics', fn ($data) => $data['available'] && $data['amount'] === $expectedPaid)
            ->assertSee('325,50 VNĐ');
        $this->assertCount(3, $queries);
        foreach ($queries as $query) {
            $this->assertDoesNotMatchRegularExpression('/^\s*(insert|update|delete|replace|alter|drop)\b/i', $query['query']);
            $this->assertDoesNotMatchRegularExpression('/\b(join|reservations|order_items|areas)\b/i', $query['query']);
            $this->assertGreaterThanOrEqual(0, $query['time']);
        }
        foreach ($sources as $source) {
            $this->assertSame($before[$source], DB::table($source)->orderBy('id')->get()->toJson());
        }
        file_put_contents(storage_path('logs/dashboard-test-performance.json'), json_encode([
            'engine' => 'sqlite :memory:', 'orders' => 3, 'payments' => 4, 'current_tables' => 2,
            'query_count' => count($queries), 'query_ms' => array_column($queries, 'time'),
            'total_query_ms' => array_sum(array_column($queries, 'time')),
        ], JSON_PRETTY_PRINT));
    }
}
