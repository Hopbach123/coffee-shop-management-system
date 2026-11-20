<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\FinancialReportService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class FinancialReportTest extends TestCase
{
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame('sqlite', DB::connection()->getDriverName());
        $this->assertSame(':memory:', DB::connection()->getDatabaseName());
        (require database_path('migrations/0001_01_01_000000_create_users_table.php'))->up();
        Schema::create('orders', function (Blueprint $table): void {
            $table->id();
            $table->string('order_code');
            $table->string('status')->default('pending');
        });
        Schema::create('payments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id')->constrained('orders');
            $table->string('payment_code');
            $table->string('payment_method');
            $table->decimal('amount', 15, 2);
            $table->string('transaction_code')->nullable();
            $table->string('status');
            $table->dateTime('paid_at')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });
        $this->admin = User::forceCreate(['name' => 'Finance Admin', 'email' => 'finance@example.test',
            'password' => 'test-only', 'role' => 'admin', 'status' => 'active']);
        $this->withoutVite();
    }

    private function receipt(string $amount, string $method = 'cash', string $status = 'paid', ?string $at = '2026-11-18 01:00:00', ?int $orderId = null): int
    {
        $orderId ??= DB::table('orders')->insertGetId(['order_code' => uniqid('ORD-'), 'status' => 'pending']);

        return DB::table('payments')->insertGetId(['order_id' => $orderId, 'payment_code' => uniqid('PAY-'),
            'payment_method' => $method, 'amount' => $amount, 'status' => $status, 'paid_at' => $at, 'created_by' => $this->admin->id]);
    }

    public function test_report_counts_exact_paid_values_by_method_and_renders_receipts(): void
    {
        $this->receipt('0.10');
        $this->receipt('0.20');
        $this->receipt('100.25', 'bank_transfer');
        foreach (['pending', 'failed', 'refunded'] as $status) {
            $this->receipt('900.00', 'cash', $status);
        }
        $report = app(FinancialReportService::class)->report('2026-11-18', '2026-11-18');
        $this->assertTrue($report['available']);
        $this->assertSame('100.55', $report['total']);
        $this->assertSame('0.30', $report['cash']);
        $this->assertSame('100.25', $report['bank_transfer']);
        $this->assertSame(3, $report['paid_orders']);
        $this->actingAs($this->admin)->get('/admin/finance?from=2026-11-18&to=2026-11-18')
            ->assertOk()->assertSee('Báo cáo thu tiền')->assertSee('100,55')->assertSee('Finance Admin');
    }

    public function test_vietnam_day_boundaries_are_converted_to_utc(): void
    {
        $this->receipt('100.00', at: '2026-11-17 16:59:59');
        $this->receipt('1.10', at: '2026-11-17 17:00:00');
        $this->receipt('2.20', at: '2026-11-18 16:59:59');
        $this->receipt('200.00', at: '2026-11-18 17:00:00');
        $report = app(FinancialReportService::class)->report('2026-11-18', '2026-11-18');
        $this->assertSame('3.30', $report['total']);
        $this->assertSame(2, $report['paid_orders']);
    }

    public function test_duplicate_paid_outside_period_blocks_summary_and_preserves_history(): void
    {
        $id = $this->receipt('25.00');
        $orderId = DB::table('payments')->where('id', $id)->value('order_id');
        $this->receipt('25.00', at: '2026-10-01 01:00:00', orderId: $orderId);
        $report = app(FinancialReportService::class)->report('2026-11-18', '2026-11-18');
        $this->assertFalse($report['available']);
        $this->assertNull($report['total']);
        $this->assertNull($report['paid_orders']);
        $this->assertDatabaseCount('payments', 2);
    }

    public function test_empty_period_and_undated_history_are_explicit(): void
    {
        $this->receipt('99.00', at: null);
        $report = app(FinancialReportService::class)->report('2026-11-18', '2026-11-18');
        $this->assertSame('0.00', $report['total']);
        $this->assertSame(0, $report['paid_orders']);
        $this->assertSame(1, $report['undated_paid']);
    }

    public function test_report_rejects_invalid_dates_and_non_admin_access(): void
    {
        $this->get('/admin/finance')->assertRedirect(route('login'));
        $staff = User::forceCreate(['name' => 'Staff', 'email' => 'staff-finance@example.test',
            'password' => 'test-only', 'role' => 'staff', 'status' => 'active']);
        $this->actingAs($staff)->get('/admin/finance')->assertForbidden();
        $this->actingAs($this->admin)->get('/admin/finance?from=2026-11-19&to=2026-11-18')->assertSessionHasErrors('to');
        $this->get('/admin/finance?from=invalid')->assertSessionHasErrors('from');
        $this->admin->forceFill(['status' => 'inactive'])->save();
        $this->get('/admin/finance')->assertForbidden();
    }

    public function test_finance_migration_preserves_legacy_receipts_and_enforces_new_reference_key(): void
    {
        $id = $this->receipt('25.00', 'bank_transfer');
        $before = DB::table('payments')->where('id', $id)->first();
        $migration = require database_path('migrations/2026_11_18_000001_add_finance_receipt_keys_to_payments_table.php');
        $migration->up();
        $after = DB::table('payments')->where('id', $id)->first();
        $this->assertSame($before->amount, $after->amount);
        $this->assertNull($after->operation_key_hash);
        $this->assertNull($after->bank_reference_hash);
        DB::table('payments')->where('id', $id)->update(['bank_reference_hash' => hash('sha256', 'reference')]);
        $second = $this->receipt('30.00', 'bank_transfer');
        try {
            DB::table('payments')->where('id', $second)->update(['bank_reference_hash' => hash('sha256', 'reference')]);
            $this->fail('Duplicate bank reference hash accepted.');
        } catch (\Illuminate\Database\UniqueConstraintViolationException) {
            $this->assertDatabaseCount('payments', 2);
        }
        $migration->down();
        $this->assertSame($before->amount, DB::table('payments')->where('id', $id)->value('amount'));
    }
}
