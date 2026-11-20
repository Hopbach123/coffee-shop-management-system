<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Dashboard\PeriodStatisticsService;
use Carbon\CarbonImmutable;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DashboardPeriodStatisticsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame('sqlite', DB::connection()->getDriverName());
        $this->assertSame(':memory:', DB::connection()->getDatabaseName());
        $this->travelTo(CarbonImmutable::parse('2026-11-20 10:00:00', 'Asia/Ho_Chi_Minh'));
        foreach (['0001_01_01_000000_create_users_table.php', '2026_08_14_000002_create_areas_table.php', '2026_08_14_000006_create_cafe_tables_table.php'] as $migration) {
            (require database_path('migrations/'.$migration))->up();
        }
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('status');
            $table->decimal('total_amount', 15, 2);
            $table->dateTime('ordered_at');
            $table->dateTime('completed_at')->nullable();
        });
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->string('status');
            $table->decimal('amount', 15, 2);
            $table->dateTime('paid_at')->nullable();
        });
        $this->withoutVite();
    }

    private function order(string $orderedAt, string $status = 'completed', ?string $completedAt = null, string $amount = '100.00'): int
    {
        return DB::table('orders')->insertGetId(['ordered_at' => $orderedAt, 'status' => $status, 'completed_at' => $completedAt, 'total_amount' => $amount]);
    }

    private function pay(int $orderId, ?string $paidAt, string $status = 'paid', string $amount = '12.34'): void
    {
        DB::table('payments')->insert(['order_id' => $orderId, 'paid_at' => $paidAt, 'status' => $status, 'amount' => $amount]);
    }

    public function test_local_day_boundaries_and_receipt_date_are_independent_of_order_completion(): void
    {
        $order = $this->order('2026-11-13 17:00:00', 'completed', '2026-11-14 17:05:00');
        $this->pay($order, '2026-11-13 17:00:00');
        $this->pay($this->order('2026-11-13 16:59:59'), '2026-11-13 16:59:59');
        $this->pay($this->order('2026-11-20 16:59:59'), '2026-11-20 16:59:59', 'paid', '20.25');
        $this->pay($this->order('2026-11-20 17:00:00'), '2026-11-20 17:00:00');
        $data = app(PeriodStatisticsService::class)->summarize(7, true);
        $this->assertSame('14/11/2026 – 20/11/2026', $data['range']);
        $this->assertSame('32.59', $data['paid']);
        $this->assertSame(2, $data['orders_count']);
        $this->assertSame('12.34', $data['rows'][0]['paid']);
        $this->assertSame('0.00', $data['rows'][0]['completed']);
        $this->assertSame('100.00', $data['rows'][1]['completed']);
        $this->assertSame('100.00', $data['average']);
    }

    public function test_cancelled_and_pending_orders_do_not_inflate_completed_value_or_paid_receipts(): void
    {
        $completed = $this->order('2026-11-20 01:00:00', 'completed', '2026-11-20 02:00:00', '30.00');
        $this->order('2026-11-20 01:00:00', 'cancelled', '2026-11-20 02:00:00', '900.00');
        $this->order('2026-11-20 01:00:00', 'pending');
        $this->pay($completed, '2026-11-20 02:00:00', 'paid', '30.00');
        foreach (['pending', 'failed', 'refunded'] as $status) {
            $this->pay($completed, '2026-11-20 02:00:00', $status, '500.00');
        }
        $data = app(PeriodStatisticsService::class)->summarize(7, true, 'completed');
        $this->assertSame('30.00', $data['completed']);
        $this->assertSame('30.00', $data['paid']);
        $this->assertSame(3, $data['orders_count']);
        $this->assertSame(['cancelled' => 1, 'completed' => 1, 'pending' => 1], $data['statuses']);
    }

    public function test_unverified_or_undated_paid_records_are_not_presented_as_verified_zero(): void
    {
        $this->pay($this->order('2026-11-20 01:00:00'), null);
        $this->assertFalse(app(PeriodStatisticsService::class)->summarize(7, true)['paid_available']);
        $this->assertFalse(app(PeriodStatisticsService::class)->summarize(30, false)['paid_available']);
        $admin = User::forceCreate(['name' => 'Admin', 'email' => 'period@example.test', 'password' => 'test-only', 'role' => 'admin', 'status' => 'active']);
        $this->actingAs($admin, 'web')->get('/admin')->assertOk()->assertSee('Cần đối soát dữ liệu')->assertSee('Chưa thể xác nhận số liệu thu tiền');
    }

    public function test_empty_period_has_thirty_zero_days_and_valid_coordinates_without_writes(): void
    {
        DB::enableQueryLog();
        $data = app(PeriodStatisticsService::class)->summarize(30, true);
        foreach (DB::getQueryLog() as $query) {
            $this->assertDoesNotMatchRegularExpression('/^\s*(insert|update|delete|replace|alter|drop)\b/i', $query['query']);
        }
        DB::disableQueryLog();
        $this->assertCount(30, $data['rows']);
        $this->assertSame('0.00', $data['paid']);
        $this->assertNull($data['average']);
        $this->assertFalse($data['has_chart_data']);
        foreach ($data['rows'] as $row) {
            $this->assertTrue(is_finite($row['x']) && is_finite($row['y']));
            $this->assertEquals(224, $row['y']);
        }
    }

    public function test_dashboard_filters_and_metric_links_preserve_the_canonical_admin_page(): void
    {
        $admin = User::forceCreate(['name' => 'Admin', 'email' => 'period-ui@example.test', 'password' => 'test-only', 'role' => 'admin', 'status' => 'active']);
        $this->actingAs($admin, 'web')->get('/admin?days=30&metric=completed')->assertOk()->assertViewIs('admin.home')
            ->assertViewHas('period', fn ($data) => $data['days'] === 30 && $data['metric'] === 'completed')
            ->assertSee('overview-chart__svg')->assertSee('Xem số liệu từng ngày')->assertSee('Ngày Việt Nam');
        $this->get('/admin?days[]=30&metric[]=completed')->assertOk()
            ->assertViewHas('period', fn ($data) => $data['days'] === 7 && $data['metric'] === 'paid');
        $this->get('/admin/dashboard')->assertRedirect(route('admin.home'));
    }
}
