<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Promotion;
use App\Models\User;
use App\Services\FinancialReportService;
use App\Services\OrderService;
use App\Services\PromotionService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class FinanceMySqlTest extends TestCase
{
    private bool $ownsSchema = false;

    private string $schemaName;

    protected function setUp(): void
    {
        parent::setUp();
        $database = getenv('CAFE_FINANCE_MYSQL_DATABASE');
        if (! $database) {
            $this->markTestSkipped('Requires an EMPTY dedicated coffee_finance_qa_* MySQL database.');
        }
        $this->assertMatchesRegularExpression('/\Acoffee_finance_qa_[a-z0-9_]+\z/', $database);
        $connection = config('database.connections.mysql');
        $connection['url'] = null;
        $connection['database'] = $database;
        config(['database.default' => 'finance_mysql', 'database.connections.finance_mysql' => $connection]);
        DB::purge('finance_mysql');
        $this->assertSame('mysql', DB::connection()->getDriverName());
        $this->assertSame($database, DB::connection()->getDatabaseName());
        $this->assertSame($database, DB::selectOne('SELECT DATABASE() AS current_database')->current_database);
        $this->assertSame([], Schema::connection('finance_mysql')->getTableListing($database), 'Finance QA requires an empty schema.');
        $this->schemaName = $database;
        $this->ownsSchema = true;
        Artisan::call('migrate', ['--database' => 'finance_mysql', '--force' => true]);
    }

    protected function tearDown(): void
    {
        try {
            if ($this->ownsSchema) {
                $schema = Schema::connection('finance_mysql');
                $schema->disableForeignKeyConstraints();
                try {
                    foreach ($schema->getTableListing($this->schemaName) as $table) {
                        $this->assertStringStartsWith($this->schemaName.'.', $table);
                        $schema->dropIfExists($table);
                    }
                } finally {
                    $schema->enableForeignKeyConstraints();
                }
                DB::disconnect('finance_mysql');
            }
        } finally {
            parent::tearDown();
        }
    }

    private function race(array $payloads): array
    {
        $prefix = storage_path('framework/testing/finance-'.Str::uuid());
        if (! is_dir(dirname($prefix))) {
            mkdir(dirname($prefix), 0777, true);
        }
        $processes = [];
        try {
            foreach ($payloads as $index => $payload) {
                file_put_contents($prefix.'.'.$index.'.json', json_encode($payload, JSON_THROW_ON_ERROR));
                $process = new Process([PHP_BINARY, base_path('tests/Support/finance_mysql_worker.php'),
                    $prefix.'.'.$index.'.json', $prefix.'.ready-'.$index, $prefix.'.go'], base_path());
                $process->setTimeout(25);
                $process->start();
                $processes[] = $process;
            }
            $deadline = microtime(true) + 15;
            while (! file_exists($prefix.'.ready-0') || ! file_exists($prefix.'.ready-1')) {
                if (microtime(true) > $deadline) {
                    $this->fail('Finance workers did not reach the barrier.');
                }
                usleep(10000);
            }
            file_put_contents($prefix.'.go', 'go');
            $results = [];
            foreach ($processes as $process) {
                $process->wait();
                $this->assertTrue($process->isSuccessful(), $process->getOutput().$process->getErrorOutput());
                $results[] = json_decode($process->getOutput(), true, 512, JSON_THROW_ON_ERROR);
            }

            return $results;
        } finally {
            foreach ($processes as $process) {
                if ($process->isRunning()) {
                    $process->stop();
                }
            }
            foreach (glob($prefix.'.*') as $path) {
                unlink($path);
            }
        }
    }

    public function test_mysql_receipt_replay_cashier_and_reference_races_and_strict_report(): void
    {
        $actor = User::forceCreate(['name' => 'Finance QA', 'email' => 'finance-qa@example.test',
            'password' => 'test-only', 'role' => 'staff', 'status' => 'active']);
        $otherActor = User::forceCreate(['name' => 'Second cashier', 'email' => 'cashier-qa@example.test',
            'password' => 'test-only', 'role' => 'staff', 'status' => 'active']);
        $category = Category::forceCreate(['name' => 'QA category', 'status' => 'active']);
        $product = Product::forceCreate(['category_id' => $category->id, 'name' => 'QA Latte', 'slug' => 'finance-qa-latte', 'status' => 'available']);
        $variant = ProductVariant::forceCreate(['product_id' => $product->id, 'name' => 'M', 'price' => '25.10', 'status' => 'available']);
        $orders = [];
        for ($index = 0; $index < 6; $index++) {
            $order = app(OrderService::class)->create(['order_type' => 'takeaway'], $actor);
            app(OrderService::class)->addItem($order, ['variant_id' => $variant->id, 'quantity' => 1], $actor);
            $orders[] = $order;
        }
        $payload = ['order' => $orders[0]->id, 'actor' => $actor->id, 'key' => 'same-operation', 'method' => 'cash'];
        $results = $this->race([$payload, $payload]);
        $this->assertSame(['ok', 'ok'], array_column($results, 'status'));
        $this->assertSame($results[0]['id'], $results[1]['id']);
        $this->assertEqualsCanonicalizing([false, true], array_column($results, 'replay'));
        $cashiers = $this->race([
            ['order' => $orders[1]->id, 'actor' => $actor->id, 'key' => 'cashier-one', 'method' => 'cash'],
            ['order' => $orders[1]->id, 'actor' => $otherActor->id, 'key' => 'cashier-two', 'method' => 'cash'],
        ]);
        $this->assertEqualsCanonicalizing(['ok', 'rejected'], array_column($cashiers, 'status'));
        $bank = $this->race([
            ['order' => $orders[2]->id, 'actor' => $actor->id, 'key' => 'bank-one', 'method' => 'bank_transfer', 'reference' => 'QA-BANK-REF'],
            ['order' => $orders[3]->id, 'actor' => $otherActor->id, 'key' => 'bank-two', 'method' => 'bank_transfer', 'reference' => 'QA-BANK-REF'],
        ]);
        $this->assertEqualsCanonicalizing(['ok', 'rejected'], array_column($bank, 'status'));
        $promotion = Promotion::forceCreate(['code' => 'LAST-USE', 'name' => 'Last use', 'discount_type' => 'fixed',
            'discount_value' => '1.10', 'minimum_order' => '0.00', 'start_at' => now()->subDay(),
            'end_at' => now()->addDay(), 'status' => 'active', 'usage_limit' => 1, 'used_count' => 0]);
        app(PromotionService::class)->applyToOrder($orders[4], $promotion->code);
        app(PromotionService::class)->applyToOrder($orders[5], $promotion->code);
        $quota = $this->race([
            ['order' => $orders[4]->id, 'actor' => $actor->id, 'key' => 'quota-one', 'method' => 'cash'],
            ['order' => $orders[5]->id, 'actor' => $otherActor->id, 'key' => 'quota-two', 'method' => 'cash'],
        ]);
        $this->assertEqualsCanonicalizing(['ok', 'rejected'], array_column($quota, 'status'));
        $this->assertSame(1, $promotion->fresh()->used_count);
        $this->assertSame(4, Payment::where('status', 'paid')->count());
        $report = app(FinancialReportService::class)->report('2026-01-01', '2099-12-31');
        $this->assertTrue($report['available']);
        $this->assertSame('99.30', $report['total']);
        $this->assertSame('74.20', $report['cash']);
        $this->assertSame('25.10', $report['bank_transfer']);
        $this->assertSame(6, Order::where('status', 'pending')->count());
    }
}
