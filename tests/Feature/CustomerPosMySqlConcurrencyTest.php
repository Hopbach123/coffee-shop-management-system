<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\CustomerOrderRequest;
use App\Models\Ingredient;
use App\Models\InventoryTransaction;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductRecipe;
use App\Models\ProductVariant;
use App\Models\User;
use App\Services\CustomerCartService;
use App\Services\CustomerOrderService;
use App\Services\PosCheckoutService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class CustomerPosMySqlConcurrencyTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $database = getenv('CAFE_WORKFLOW_MYSQL_DATABASE');
        if (! $database) {
            $this->markTestSkipped('Requires a migrated, disposable coffee_workflow_qa* MySQL database and CAFE_WORKFLOW_MYSQL_DATABASE.');
        }
        $this->assertMatchesRegularExpression('/^coffee_workflow_qa(?:_[a-z0-9_]+)?$/', $database);
        $connection = config('database.connections.mysql');
        $connection['url'] = null;
        $connection['host'] = '127.0.0.1';
        $connection['port'] = getenv('CAFE_WORKFLOW_MYSQL_PORT') ?: '3308';
        $connection['database'] = $database;
        $connection['username'] = getenv('CAFE_WORKFLOW_MYSQL_USERNAME') ?: 'root';
        $connection['password'] = getenv('CAFE_WORKFLOW_MYSQL_PASSWORD') ?: '';
        $this->assertNotSame('3306', (string) $connection['port'], 'Use a separate QA instance.');
        config(['database.default' => 'workflow_mysql', 'database.connections.workflow_mysql' => $connection]);
        DB::purge('workflow_mysql');
        $this->assertSame('mysql', DB::connection()->getDriverName());
        $this->assertTrue(Schema::hasColumn('orders', 'pos_checkout_key_hash'));
        $this->assertTrue(Schema::hasColumn('orders', 'delivery_fee'));
        $this->assertTrue(Schema::hasTable('customer_order_requests'));
    }

    private function fixture(): array
    {
        $suffix = (string) Str::ulid();
        $actor = User::forceCreate(['name' => 'Workflow QA', 'email' => $suffix.'@example.test', 'password' => 'test-only', 'role' => 'staff', 'status' => 'active']);
        $category = Category::forceCreate(['name' => 'QA-'.$suffix, 'status' => 'active']);
        $product = Product::forceCreate(['category_id' => $category->id, 'name' => 'QA Coffee', 'slug' => strtolower($suffix), 'status' => 'available']);
        $variant = ProductVariant::forceCreate(['product_id' => $product->id, 'name' => 'M', 'price' => '35.50', 'status' => 'available']);
        $ingredient = Ingredient::forceCreate(['name' => 'QA-'.$suffix, 'unit' => 'g', 'current_stock' => '100.000', 'status' => 'active']);
        ProductRecipe::forceCreate(['variant_id' => $variant->id, 'ingredient_id' => $ingredient->id, 'quantity' => '2.500']);
        $draft = app(CustomerCartService::class)->add([], ['variant_id' => $variant->id, 'quantity' => 2, 'note' => 'Concurrent QA']);

        return [$actor, $ingredient, $draft];
    }

    private function race(array $payload): array
    {
        $prefix = storage_path('framework/testing/workflow-race-'.Str::uuid());
        $payloadPath = $prefix.'.json';
        $goPath = $prefix.'.go';
        file_put_contents($payloadPath, json_encode($payload, JSON_THROW_ON_ERROR));
        $processes = [];
        try {
            for ($i = 0; $i < 2; $i++) {
                $process = new Process([PHP_BINARY, base_path('tests/Support/customer_pos_mysql_worker.php'), $payloadPath, $prefix.'.ready-'.$i, $goPath], base_path());
                $process->setTimeout(25);
                $process->start();
                $processes[] = $process;
            }
            $deadline = microtime(true) + 15;
            while (! file_exists($prefix.'.ready-0') || ! file_exists($prefix.'.ready-1')) {
                if (microtime(true) > $deadline) {
                    $this->fail('Concurrent QA workers did not reach the barrier.');
                }
                usleep(10000);
            }
            file_put_contents($goPath, 'go');
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
            foreach ([$payloadPath, $goPath, $prefix.'.ready-0', $prefix.'.ready-1'] as $path) {
                if (file_exists($path)) {
                    unlink($path);
                }
            }
        }
    }

    public function test_two_pos_workers_create_one_order_paid_and_consumption(): void
    {
        [$actor, $ingredient, $draft] = $this->fixture();
        $results = $this->race(['mode' => 'pos', 'actor' => $actor->id, 'draft' => $draft,
            'attributes' => ['order_type' => 'takeaway', 'payment_method' => 'cash', 'received_confirmation' => true],
            'key' => bin2hex(random_bytes(32)), 'quote_hash' => app(PosCheckoutService::class)->review($draft)['fingerprint']]);
        $this->assertSame(['ok', 'ok'], array_column($results, 'status'));
        $this->assertSame($results[0]['id'], $results[1]['id']);
        $orders = Order::where('user_id', $actor->id)->get();
        $this->assertCount(1, $orders);
        $this->assertSame(1, Payment::where('order_id', $orders->sole()->id)->where('status', 'paid')->count());
        $this->assertSame(1, InventoryTransaction::where('ingredient_id', $ingredient->id)->count());
        $this->assertSame('95.000', $ingredient->fresh()->current_stock);
    }

    public function test_concurrent_submit_accept_and_bank_payment_preserve_one_result_per_step(): void
    {
        [$actor, $ingredient, $draft] = $this->fixture();
        $results = $this->race(['mode' => 'submit', 'draft' => $draft, 'key' => bin2hex(random_bytes(32)),
            'token' => bin2hex(random_bytes(32)), 'quote_hash' => app(CustomerOrderService::class)->review($draft)['fingerprint'],
            'attributes' => ['customer_name' => 'QA Guest', 'customer_phone' => '0900000000', 'customer_address' => 'QA Test Address', 'payment_option' => 'bank_transfer_prepaid']]);
        $this->assertSame(['ok', 'ok'], array_column($results, 'status'));
        $this->assertSame($results[0]['id'], $results[1]['id']);
        $request = CustomerOrderRequest::findOrFail($results[0]['id']);
        $accepted = $this->race(['mode' => 'accept', 'request' => $request->id, 'actor' => $actor->id]);
        $this->assertSame(['ok', 'ok'], array_column($accepted, 'status'));
        $this->assertSame($accepted[0]['id'], $accepted[1]['id']);
        $orderId = $accepted[0]['id'];
        $paid = $this->race(['mode' => 'transfer', 'order' => $orderId, 'actor' => $actor->id,
            'attributes' => ['payment_method' => 'bank_transfer', 'received_confirmation' => true]]);
        $this->assertEqualsCanonicalizing(['ok', 'rejected'], array_column($paid, 'status'));
        $this->assertSame(1, Order::where('user_id', $actor->id)->count());
        $this->assertSame(1, Payment::where('order_id', $orderId)->where('status', 'paid')->count());
        $this->assertSame(1, InventoryTransaction::where('ingredient_id', $ingredient->id)->count());
        $this->assertSame('95.000', $ingredient->fresh()->current_stock);
    }
}
