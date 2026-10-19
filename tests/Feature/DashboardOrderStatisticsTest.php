<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\CafeTable;
use App\Models\Customer;
use App\Models\User;
use App\Services\Dashboard\OrderStatisticsService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DashboardOrderStatisticsTest extends TestCase
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

    private function order(string $status, array $attributes = []): void
    {
        DB::table('orders')->insert(array_merge(['order_code' => 'ORDER-'.(DB::table('orders')->count() + 1), 'user_id' => $this->admin->id,
            'status' => $status, 'ordered_at' => '2026-01-01 12:00:00'], $attributes));
    }

    public function test_empty_database_returns_integer_zeros_and_renders_honest_definition(): void
    {
        $expected = ['total' => 0, 'by_status' => ['pending' => 0, 'completed' => 0, 'cancelled' => 0]];
        $this->assertSame($expected, app(OrderStatisticsService::class)->summarize());
        $this->actingAs($this->admin, 'web')->get('/admin/dashboard')->assertOk()->assertViewIs('admin.dashboard.index')
            ->assertViewHas('orderStatistics', $expected)->assertSee('Tổng đơn hàng')->assertSee('Chưa có đơn hàng được ghi nhận.')
            ->assertSee('Bao gồm đơn đang xử lý, hoàn tất và đã hủy.')->assertSee('không giới hạn ngày')
            ->assertSee('dashboard-orders__total">0</p>', false)->assertDontSee('NaN');
    }

    public function test_total_counts_each_record_including_cancelled_and_ignores_unrequested_filters(): void
    {
        $this->order('pending');
        $this->assertSame(1, app(OrderStatisticsService::class)->summarize()['total']);
        $this->order('pending');
        $this->order('completed', ['ordered_at' => '2000-01-01 00:00:00', 'completed_at' => '2000-01-02 00:00:00']);
        $this->order('cancelled', ['ordered_at' => '2030-01-01 00:00:00']);
        $expected = ['total' => 4, 'by_status' => ['pending' => 2, 'completed' => 1, 'cancelled' => 1]];
        $this->assertSame($expected, app(OrderStatisticsService::class)->summarize());
        $this->actingAs($this->admin, 'web')->get('/admin/dashboard?status=completed&date=2026-10-19')->assertOk()->assertViewHas('orderStatistics', $expected);
    }

    public function test_shared_nullable_and_soft_deleted_masters_do_not_drop_or_duplicate_orders(): void
    {
        $customer = Customer::create(['name' => 'Customer']);
        $area = Area::create(['name' => 'Floor']);
        $table = CafeTable::create(['area_id' => $area->id, 'table_code' => 'T1', 'capacity' => 4]);
        foreach (['pending', 'completed', 'cancelled'] as $status) {
            $this->order($status, ['customer_id' => $customer->id, 'table_id' => $table->id]);
        }
        $this->order('pending', ['customer_id' => null, 'table_id' => null, 'reservation_id' => null, 'note' => null, 'completed_at' => null]);
        $customer->delete();
        $table->delete();
        $stats = app(OrderStatisticsService::class)->summarize();
        $this->assertSame(4, $stats['total']);
        $this->assertSame(DB::table('orders')->count(), array_sum($stats['by_status']));
        $this->assertSame(['pending' => 2, 'completed' => 1, 'cancelled' => 1], $stats['by_status']);
    }

    public function test_request_uses_one_order_aggregate_without_joins_hydration_or_business_writes(): void
    {
        $this->order('cancelled');
        $this->order('completed');
        $before = DB::table('orders')->get()->toJson();
        $this->actingAs($this->admin, 'web');
        DB::enableQueryLog();
        $this->get('/admin/dashboard')->assertOk();
        $orderQueries = [];
        foreach (DB::getQueryLog() as $query) {
            $sql = $query['query'];
            $this->assertDoesNotMatchRegularExpression('/^\s*(insert|update|delete|replace|alter|drop)\b/i', $sql);
            $this->assertDoesNotMatchRegularExpression('/\b(join|order_items|customers)\b/i', $sql);
            if (str_contains($sql, '"orders"')) {
                $orderQueries[] = $sql;
            }
        }
        DB::disableQueryLog();
        DB::flushQueryLog();
        $this->assertCount(1, $orderQueries);
        $this->assertStringContainsString('COUNT(*)', $orderQueries[0]);
        $this->assertStringContainsString('group by', $orderQueries[0]);
        $this->assertStringNotContainsString('select *', $orderQueries[0]);
        $this->assertSame($before, DB::table('orders')->get()->toJson());
    }

    public function test_larger_counts_are_formatted_and_query_size_stays_grouped(): void
    {
        $rows = [];
        for ($i = 0; $i < 1234; $i++) {
            $rows[] = ['order_code' => 'BULK-'.$i, 'user_id' => $this->admin->id, 'status' => 'pending', 'ordered_at' => '2026-01-01 00:00:00'];
        }
        foreach (array_chunk($rows, 100) as $chunk) {
            DB::table('orders')->insert($chunk);
        }
        DB::enableQueryLog();
        $stats = app(OrderStatisticsService::class)->summarize();
        $this->assertCount(1, DB::getQueryLog());
        DB::disableQueryLog();
        DB::flushQueryLog();
        $this->assertSame(1234, $stats['total']);
        $this->assertCount(3, $stats['by_status']);
        $this->actingAs($this->admin, 'web')->get('/admin/dashboard')->assertOk()->assertSee('1.234');
    }

    public function test_dashboard_is_admin_only_and_existing_home_links_to_it(): void
    {
        $this->get('/admin/dashboard')->assertRedirect(route('login'));
        $this->getJson('/admin/dashboard')->assertUnauthorized();
        $this->admin->forceFill(['role' => 'staff'])->save();
        $this->actingAs($this->admin, 'web')->get('/admin/dashboard')->assertForbidden();
        $this->admin->forceFill(['role' => 'admin'])->save();
        $this->actingAs($this->admin, 'web')->get('/admin/dashboard')->assertOk();
        $this->get('/admin')->assertOk()->assertSee(route('admin.dashboard.index'));
        $route = Route::getRoutes()->getByName('admin.dashboard.index');
        $this->assertSame('admin/dashboard', $route->uri());
        $this->assertSame(['GET', 'HEAD'], $route->methods());
        $this->assertContains('auth:web', $route->gatherMiddleware());
        $this->assertContains('role:admin', $route->gatherMiddleware());
    }
}
