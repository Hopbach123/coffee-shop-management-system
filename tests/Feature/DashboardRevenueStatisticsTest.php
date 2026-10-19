<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Dashboard\RevenueStatisticsService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DashboardRevenueStatisticsTest extends TestCase
{
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame('sqlite', DB::connection()->getDriverName());
        $this->assertSame(':memory:', DB::connection()->getDatabaseName());
        (require database_path('migrations/0001_01_01_000000_create_users_table.php'))->up();
        // Existing-schema subsets for read-only aggregation; MySQL DECIMAL/CHECK behavior requires MySQL acceptance.
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->enum('status', ['pending', 'completed', 'cancelled'])->default('pending');
            $table->decimal('total_amount', 15, 2)->default(0);
        });
        foreach (['2026_08_14_000002_create_areas_table.php', '2026_08_14_000006_create_cafe_tables_table.php'] as $migration) {
            (require database_path('migrations/'.$migration))->up();
        }
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->restrictOnDelete();
            $table->string('payment_code', 50)->unique();
            $table->enum('payment_method', ['cash', 'bank_transfer']);
            $table->decimal('amount', 15, 2);
            $table->string('transaction_code', 100)->nullable();
            $table->enum('status', ['pending', 'paid', 'failed', 'refunded'])->default('pending');
            $table->dateTime('paid_at')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });
        $this->admin = User::forceCreate(['name' => 'Admin', 'email' => 'revenue@example.test', 'password' => 'test-only-password', 'role' => 'admin', 'status' => 'active']);
        $this->withoutVite();
    }

    private function order(string $status = 'completed'): int
    {
        return DB::table('orders')->insertGetId(['status' => $status, 'total_amount' => '999.00']);
    }

    private function payment(int $orderId, string $status, string $amount, array $attributes = []): void
    {
        DB::table('payments')->insert(array_merge(['order_id' => $orderId, 'status' => $status, 'amount' => $amount, 'payment_method' => 'cash',
            'payment_code' => 'PRIVATE-PAY-'.(DB::table('payments')->count() + 1), 'created_by' => $this->admin->id], $attributes));
    }

    public function test_empty_data_returns_exact_zero_and_preserves_order_kpi(): void
    {
        $this->assertSame(['available' => true, 'amount' => '0.00', 'reason' => null], app(RevenueStatisticsService::class)->summarize());
        $this->actingAs($this->admin, 'web')->get('/admin/dashboard')->assertOk()->assertSee('Tổng đơn hàng')->assertSee('Tổng thanh toán thành công')
            ->assertSee('0,00 VNĐ')->assertSee('không phải doanh thu thuần')->assertDontSee('Chưa xác định');
    }

    public function test_only_paid_values_count_with_retries_and_refunded_excluded(): void
    {
        $order = $this->order();
        $this->payment($order, 'pending', '100.00');
        $this->payment($order, 'failed', '100.00');
        $this->payment($order, 'refunded', '100.00');
        $this->payment($order, 'paid', '12.34');
        $this->assertSame('12.34', app(RevenueStatisticsService::class)->summarize()['amount']);
        $this->actingAs($this->admin, 'web')->get('/admin/dashboard?date=2000-01-01')->assertOk()->assertSee('12,34 VNĐ')
            ->assertViewHas('orderStatistics', fn ($value) => $value['total'] === 1)->assertDontSee('PRIVATE-PAY-');
    }

    public function test_non_paid_only_is_zero_and_not_an_unknown_formula(): void
    {
        $order = $this->order();
        foreach (['pending', 'failed', 'refunded'] as $status) {
            $this->payment($order, $status, '123.45');
        }
        $this->assertSame(['available' => true, 'amount' => '0.00', 'reason' => null], app(RevenueStatisticsService::class)->summarize());
    }

    public function test_equal_amounts_and_shared_nonunique_reference_are_not_distincted(): void
    {
        $this->payment($this->order('cancelled'), 'paid', '100.25', ['transaction_code' => 'PRIVATE-REFERENCE']);
        $this->payment($this->order(), 'paid', '100.25', ['transaction_code' => 'PRIVATE-REFERENCE']);
        $this->assertSame('200.50', app(RevenueStatisticsService::class)->summarize()['amount']);
        $this->actingAs($this->admin, 'web')->get('/admin/dashboard')->assertOk()->assertSee('200,50 VNĐ')->assertDontSee('PRIVATE-REFERENCE');
    }

    public function test_multiple_paid_per_order_marks_value_unavailable_without_choosing_or_repairing(): void
    {
        $order = $this->order();
        $this->payment($order, 'paid', '20.00');
        $this->payment($order, 'paid', '30.00');
        $before = DB::table('payments')->get()->toJson();
        $this->assertSame(['available' => false, 'amount' => null, 'reason' => 'multiple_paid_per_order'], app(RevenueStatisticsService::class)->summarize());
        $this->actingAs($this->admin, 'web')->get('/admin/dashboard')->assertOk()->assertSee('Chưa xác định — cần kiểm tra dữ liệu thanh toán.')
            ->assertDontSee('50,00 VNĐ')->assertDontSee('0,00 VNĐ')->assertSee('Tổng đơn hàng');
        $this->assertSame($before, DB::table('payments')->get()->toJson());
    }

    public function test_sqlite_decimal_sum_handles_different_engine_string_representations(): void
    {
        $this->payment($this->order(), 'paid', '0.10');
        $this->payment($this->order(), 'paid', '0.20');
        // SQLite versions may stringify this sum as 0.3 or expose extra floating digits.
        // Neither representation establishes MySQL DECIMAL accuracy.
        $this->assertContains(app(RevenueStatisticsService::class)->summarize(), [
            ['available' => true, 'amount' => '0.30', 'reason' => null],
            ['available' => false, 'amount' => null, 'reason' => 'invalid_aggregate'],
        ], true);
        // Exact decimal-string presentation beyond float precision, independent of SQLite SUM semantics.
        $html = view('admin.dashboard._vnd', ['amount' => '19999999999999.98'])->render();
        $this->assertStringContainsString('19.999.999.999.999,98 VNĐ', $html);
    }

    public function test_aggregate_with_more_than_two_decimal_places_is_rejected(): void
    {
        // SQLite permits this fixture despite decimal(15, 2); MySQL does not.
        // Use a deterministic third decimal to exercise the precision guard.
        $this->payment($this->order(), 'paid', '0.001');
        $this->assertSame(['available' => false, 'amount' => null, 'reason' => 'invalid_aggregate'], app(RevenueStatisticsService::class)->summarize());
    }

    public function test_dashboard_has_one_payment_aggregate_and_remains_read_only(): void
    {
        $this->payment($this->order(), 'paid', '10.01');
        $before = [DB::table('orders')->get()->toJson(), DB::table('payments')->get()->toJson()];
        $this->actingAs($this->admin, 'web');
        DB::enableQueryLog();
        $this->get('/admin/dashboard')->assertOk();
        $queries = DB::getQueryLog();
        DB::disableQueryLog();
        DB::flushQueryLog();
        $paymentQueries = [];
        foreach ($queries as $query) {
            $this->assertDoesNotMatchRegularExpression('/^\s*(insert|update|delete|replace|alter|drop)\b/i', $query['query']);
            $this->assertDoesNotMatchRegularExpression('/\b(join|order_items)\b/i', $query['query']);
            if (str_contains($query['query'], '"payments"')) {
                $paymentQueries[] = $query;
            }
        }
        $this->assertCount(1, $paymentQueries);
        $this->assertStringContainsString('SUM(', $paymentQueries[0]['query']);
        $this->assertContains('paid', $paymentQueries[0]['bindings']);
        $this->assertSame($before, [DB::table('orders')->get()->toJson(), DB::table('payments')->get()->toJson()]);
    }

    public function test_revenue_uses_existing_admin_authorization(): void
    {
        $this->get('/admin/dashboard')->assertRedirect(route('login'));
        $this->getJson('/admin/dashboard')->assertUnauthorized();
        $this->admin->forceFill(['role' => 'staff'])->save();
        $this->actingAs($this->admin, 'web')->get('/admin/dashboard')->assertForbidden();
        $this->admin->forceFill(['role' => 'admin'])->save();
        $this->actingAs($this->admin, 'web')->get('/admin/dashboard')->assertOk()->assertSee('Tổng thanh toán thành công');
    }
}
