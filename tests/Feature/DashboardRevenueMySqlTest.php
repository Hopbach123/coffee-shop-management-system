<?php

namespace Tests\Feature;

use App\Services\Dashboard\RevenueStatisticsService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DashboardRevenueMySqlTest extends TestCase
{
    private array $created = [];

    protected function setUp(): void
    {
        parent::setUp();
        $database = getenv('DASHBOARD_TEST_MYSQL_DATABASE');
        if (! $database) {
            $this->markTestSkipped('Requires an EMPTY dedicated coffee_dashboard_test_* MySQL schema via DASHBOARD_TEST_MYSQL_DATABASE.');
        }
        $this->assertMatchesRegularExpression('/^coffee_dashboard_test_[a-z0-9_]+$/', $database);
        $connection = config('database.connections.mysql');
        $connection['url'] = null;
        $connection['database'] = $database;
        $connection['engine'] = 'InnoDB';
        foreach (['host', 'port', 'username', 'password'] as $key) {
            $value = getenv('DASHBOARD_TEST_MYSQL_'.strtoupper($key));
            if ($value !== false) {
                $connection[$key] = $value;
            }
        }
        config(['database.default' => 'dashboard_test_mysql', 'database.connections.dashboard_test_mysql' => $connection]);
        DB::purge('dashboard_test_mysql');
        $this->assertSame('mysql', DB::connection()->getDriverName());
        $this->assertSame([], Schema::getTableListing(), 'Refusing to touch a non-empty schema.');
        foreach ([
            ['0001_01_01_000000_create_users_table.php', ['users', 'password_reset_tokens', 'sessions']],
            ['2026_08_14_000001_create_customers_table.php', ['customers']],
            ['2026_08_14_000002_create_areas_table.php', ['areas']],
            ['2026_08_14_000006_create_cafe_tables_table.php', ['cafe_tables']],
            ['2026_08_16_000015_create_reservations_table.php', ['reservations']],
            ['2026_08_16_000016_create_orders_table.php', ['orders']],
            ['2026_08_16_000019_create_payments_table.php', ['payments']],
        ] as [$migration, $tables]) {
            $this->created = array_merge($this->created, $tables);
            (require database_path('migrations/'.$migration))->up();
        }
    }

    protected function tearDown(): void
    {
        try {
            foreach (array_reverse($this->created) as $table) {
                Schema::dropIfExists($table);
            }
            DB::disconnect('dashboard_test_mysql');
        } finally {
            parent::tearDown();
        }
    }

    public function test_actual_mysql_decimal_sum_and_duplicate_guard(): void
    {
        $user = DB::table('users')->insertGetId(['name' => 'Test Admin', 'email' => 'decimal@example.test', 'password' => 'test-only-fixture', 'role' => 'admin']);
        $orders = [];
        foreach (['0.10', '0.20', '9999999999999.99', '9999999999999.99'] as $i => $amount) {
            $order = DB::table('orders')->insertGetId(['order_code' => 'DEC-'.$i, 'user_id' => $user, 'ordered_at' => '2026-10-19 12:00:00']);
            $orders[] = $order;
            DB::table('payments')->insert(['order_id' => $order, 'payment_code' => 'DEC-PAY-'.$i, 'payment_method' => 'cash', 'status' => 'paid', 'amount' => $amount, 'created_by' => $user]);
            if ($i === 1) {
                $this->assertSame('0.30', app(RevenueStatisticsService::class)->summarize()['amount']);
            }
        }
        $this->assertSame('20000000000000.28', app(RevenueStatisticsService::class)->summarize()['amount']);
        DB::table('payments')->insert(['order_id' => $orders[0], 'payment_code' => 'DEC-DUP', 'payment_method' => 'cash', 'status' => 'paid', 'amount' => '0.10', 'created_by' => $user]);
        $this->assertSame(['available' => false, 'amount' => null, 'reason' => 'multiple_paid_per_order'], app(RevenueStatisticsService::class)->summarize());
    }
}
