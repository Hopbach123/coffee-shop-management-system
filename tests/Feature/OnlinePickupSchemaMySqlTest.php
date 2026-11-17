<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class OnlinePickupSchemaMySqlTest extends TestCase
{
    private ?string $testDatabase = null;

    protected function setUp(): void
    {
        parent::setUp();
        if (getenv('ONLINE_SCHEMA_MYSQL_TEST') !== '1') {
            $this->markTestSkipped('Opt-in MySQL test creates/drops only its own randomly named online_schema_test_* database.');
        }
        $config = config('database.connections.mysql');
        $config['url'] = null;
        $config['database'] = 'information_schema';
        config(['database.connections.online_schema_admin' => $config]);
        $database = 'online_schema_test_'.bin2hex(random_bytes(8));
        DB::connection('online_schema_admin')->statement('CREATE DATABASE `'.$database.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->testDatabase = $database;
        $config['database'] = $database;
        config(['database.connections.online_schema_test' => $config, 'database.default' => 'online_schema_test']);
        $this->assertSame($database, DB::connection()->getDatabaseName());
        $this->assertSame([], DB::select('SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = ?', [$database]));
    }

    protected function tearDown(): void
    {
        try {
            if ($this->testDatabase !== null && preg_match('/\Aonline_schema_test_[0-9a-f]{16}\z/', $this->testDatabase)) {
                DB::purge('online_schema_test');
                DB::connection('online_schema_admin')->statement('DROP DATABASE `'.$this->testDatabase.'`');
            }
        } finally {
            parent::tearDown();
        }
    }

    private function rejected(callable $operation): void
    {
        try {
            $operation();
            $this->fail('Database must reject invalid data.');
        } catch (QueryException $exception) {
            $this->assertContains((string) $exception->getCode(), ['23000', 'HY000', '22003', '01000', '22001']);
        }
    }

    private function executeSeedSql(): void
    {
        $sql = preg_replace('/^--.*$/m', '', file_get_contents(database_path('seed_data.sql')));
        try {
            foreach (preg_split('/;\s*(?:\r?\n|$)/', $sql) as $statement) {
                if (trim($statement) !== '') {
                    $cursor = DB::connection()->getPdo()->query($statement);
                    $cursor->closeCursor();
                }
            }
        } catch (\Throwable $exception) {
            DB::connection()->getPdo()->exec('ROLLBACK');
            throw $exception;
        }
    }

    private function seedChecks(): array
    {
        $checks = [];
        $sql = preg_replace('/^--.*$/m', '', file_get_contents(database_path('verify_seed_data.sql')));
        foreach (preg_split('/;\s*(?:\r?\n|$)/', $sql) as $statement) {
            if (trim($statement) !== '') {
                foreach (DB::select($statement) as $row) {
                    $checks[$row->check_name] = (int) $row->invalid_count;
                }
            }
        }

        return $checks;
    }

    public function test_seed_v2_import_rerun_and_missing_fixture_detection(): void
    {
        $this->assertSame(0, Artisan::call('migrate', ['--database' => 'online_schema_test', '--force' => true]));
        $this->assertGreaterThan(0, $this->seedChecks()['v2_fixture_presence']);
        $this->executeSeedSql();
        foreach ($this->seedChecks() as $check => $invalid) {
            $this->assertSame(0, $invalid, $check);
        }
        $this->assertSame(6, DB::table('online_order_requests')->count());
        $this->assertSame(9, DB::table('orders')->count());
        $this->assertSame(10, DB::table('order_items')->count());
        $this->assertSame(5, DB::table('payments')->count());
        $this->assertTrue(Hash::check('DemoCoffee123!', DB::table('users')->where('email', 'admin-demo@example.test')->value('password')));
        $unchanged = DB::table('ingredients')->orderBy('id')->get();
        DB::table('users')->where('email', 'admin-demo@example.test')->update(['password' => 'local-password-must-survive-rerun']);
        $this->executeSeedSql();
        $this->assertSame(6, DB::table('online_order_requests')->count());
        $this->assertSame(9, DB::table('orders')->count());
        $this->assertSame('local-password-must-survive-rerun', DB::table('users')->where('email', 'admin-demo@example.test')->value('password'));
        $this->assertEquals($unchanged, DB::table('ingredients')->orderBy('id')->get());
        $missing = DB::table('online_order_requests')->where('request_code', 'DEMO-V2-ONLINE-PENDING')->value('id');
        DB::table('online_order_request_items')->where('request_id', $missing)->delete();
        DB::table('online_order_requests')->where('id', $missing)->delete();
        $this->assertGreaterThan(0, $this->seedChecks()['v2_fixture_presence']);
    }

    public function test_workflow_screens_use_real_data_and_existing_preparation_command(): void
    {
        $this->assertSame(0, Artisan::call('migrate', ['--database' => 'online_schema_test', '--force' => true]));
        $this->executeSeedSql();
        $this->withoutVite();
        $staff = User::where('email', 'staff-demo@example.test')->firstOrFail();
        $admin = User::where('email', 'admin-demo@example.test')->firstOrFail();
        $requestId = DB::table('online_order_requests')->where('request_code', 'DEMO-V2-ONLINE-ACCEPTED')->value('id');
        $requestUri = '/staff/online-requests/'.$requestId;
        $this->get('/staff/online-requests')->assertRedirect('/login');
        $this->get($requestUri)->assertRedirect('/login');
        $this->get('/admin/ingredients/1/history')->assertRedirect('/login');
        $this->actingAs($staff, 'web');
        $this->get('/staff/online-requests?purchase_type=scheduled')->assertOk()->assertSee('DEMO-V2-SCHEDULED-PENDING')->assertDontSee('DEMO-V2-ONLINE-PENDING');
        $this->get('/staff/online-requests?status=rejected')->assertOk()->assertSee('DEMO-V2-REJECTED')->assertDontSee('DEMO-V2-SCHEDULED-PAID');
        $this->get('/staff/online-requests?status[]=bad')->assertSessionHasErrors('status');
        $this->get($requestUri)->assertOk()->assertSee('0900000003')->assertSee('DEMO-V2-ONLINE-ORDER')->assertSee('Kem sữa')->assertDontSee('DEMO-V2-TRACK-3');
        $this->get('/staff/online-requests/999999')->assertNotFound();
        $this->get('/admin/ingredients/1/history')->assertForbidden();
        $order = Order::where('order_code', 'DEMO-V2-ONLINE-ORDER')->firstOrFail();
        $paidOrder = Order::where('order_code', 'DEMO-V2-SCHEDULED-ORDER')->firstOrFail();
        $this->get('/staff/orders/'.$paidOrder->id)->assertOk()->assertSee('DEMO-V2-PREPAID')->assertSee('Đã thu tiền');
        $this->get('/staff/brewing')->assertOk()->assertSee('DEMO-V2-ONLINE-ORDER')->assertSee('Bắt đầu pha')->assertDontSee('DEMO-V1-CANCELLED')->assertDontSee('DEMO-V1-PAID-CASH');
        $item = $order->orderItems()->firstOrFail();
        $ingredientId = DB::table('product_recipes')->where('variant_id', $item->variant_id)->orderBy('ingredient_id')->value('ingredient_id');
        $stockBefore = DB::table('ingredients')->where('id', $ingredientId)->value('current_stock');
        $this->from('/staff/brewing')->patch('/staff/orders/'.$order->id.'/items/'.$item->id.'/status', ['status' => 'preparing'])->assertSessionHasNoErrors();
        $this->assertSame('preparing', $item->fresh()->status);
        $this->assertNotSame($stockBefore, DB::table('ingredients')->where('id', $ingredientId)->value('current_stock'));
        $this->actingAs($admin, 'web');
        $this->get('/staff/brewing')->assertOk()->assertSee('Bắt đầu pha');
        $this->get('/admin/ingredients/'.$ingredientId.'/history')->assertOk()->assertSee('Tiêu hao pha chế');
        $this->get('/admin/product-variants/'.$item->variant_id.'/recipe')->assertOk()->assertSee('Định lượng')->assertSee('Lịch sử kho');
        $this->get('/admin/product-variants')->assertOk()->assertSee('Xem công thức');
        $this->get('/staff/online-requests')->assertOk();
        $this->assertSame(6, DB::table('online_order_requests')->count());
    }

    public function test_fresh_upgrade_constraints_history_and_guarded_rollback(): void
    {
        $this->assertSame(0, Artisan::call('migrate', ['--database' => 'online_schema_test', '--force' => true]));
        foreach (['online_order_requests', 'online_order_request_items', 'online_order_request_item_toppings'] as $table) {
            $this->assertTrue(Schema::hasTable($table));
        }
        $this->assertSame(0, Artisan::call('migrate:rollback', ['--database' => 'online_schema_test', '--step' => 4, '--force' => true]));
        $this->assertFalse(Schema::hasColumn('orders', 'purchase_type'));
        $actor = DB::table('users')->insertGetId(['name' => 'Schema fixture', 'email' => 'schema@example.test', 'password' => 'not-a-login-fixture', 'role' => 'staff']);
        $category = DB::table('categories')->insertGetId(['name' => 'Coffee']);
        $product = DB::table('products')->insertGetId(['category_id' => $category, 'name' => 'Coffee', 'slug' => 'schema-coffee']);
        $variant = DB::table('product_variants')->insertGetId(['product_id' => $product, 'name' => 'M', 'price' => '30000.00']);
        $topping = DB::table('toppings')->insertGetId(['name' => 'Milk', 'price' => '5000.00']);
        $legacyOrder = ['order_code' => 'LEGACY-ORDER', 'user_id' => $actor, 'order_type' => 'takeaway', 'ordered_at' => '2026-10-26 01:00:00', 'subtotal' => '30000.00', 'total_amount' => '30000.00'];
        $orderId = DB::table('orders')->insertGetId($legacyOrder);
        $legacyRow = (array) DB::table('orders')->find($orderId);
        DB::table('payments')->insert(['order_id' => $orderId, 'payment_code' => 'LEGACY-PAYMENT', 'payment_method' => 'cash', 'amount' => '30000.00', 'status' => 'paid', 'paid_at' => '2026-10-26 01:01:00', 'created_by' => $actor]);
        $legacyPayment = DB::table('payments')->first();
        $this->assertSame(0, Artisan::call('migrate', ['--database' => 'online_schema_test', '--force' => true]));
        $upgraded = (array) DB::table('orders')->find($orderId);
        $this->assertSame('counter', $upgraded['purchase_type']);
        $this->assertNull($upgraded['online_request_id']);
        unset($upgraded['purchase_type'], $upgraded['online_request_id']);
        $this->assertSame($legacyRow, $upgraded);
        $this->assertEquals($legacyPayment, DB::table('payments')->first());

        $request = ['request_code' => 'WEB-1', 'purchase_type' => 'online', 'contact_name' => 'Guest', 'contact_phone' => '0900000000', 'payment_preference' => 'cash', 'submission_key' => 'submit-1', 'tracking_token_hash' => hash('sha256', 'fixture-token')];
        $requestId = DB::table('online_order_requests')->insertGetId($request);
        foreach ([['purchase_type' => 'bad'], ['purchase_type' => 'scheduled'], ['pickup_at' => '2026-10-27 01:00:00'], ['payment_timing' => 'prepaid'], ['payment_timing' => 'invalid'], ['status' => 'bad'], ['status' => 'accepted'], ['accepted_by' => $actor], ['status' => 'rejected'], ['customer_id' => 999999], ['contact_name' => ' '], ['tracking_token_hash' => 'invalid']] as $invalid) {
            $this->rejected(fn () => DB::table('online_order_requests')->where('id', $requestId)->update($invalid));
        }
        foreach (['request_code', 'submission_key', 'tracking_token_hash'] as $unique) {
            $copy = array_replace($request, ['request_code' => 'WEB-2', 'submission_key' => 'submit-2', 'tracking_token_hash' => hash('sha256', 'other')]);
            $copy[$unique] = $request[$unique];
            $this->rejected(fn () => DB::table('online_order_requests')->insert($copy));
        }
        $scheduledId = DB::table('online_order_requests')->insertGetId(array_replace($request, ['request_code' => 'SCHEDULED', 'submission_key' => 'scheduled', 'tracking_token_hash' => hash('sha256', 'scheduled'), 'purchase_type' => 'scheduled', 'pickup_at' => '2026-10-27 01:00:00', 'payment_preference' => 'bank_transfer', 'payment_timing' => 'prepaid']));
        DB::table('online_order_requests')->where('id', $requestId)->update(['status' => 'accepted', 'accepted_by' => $actor, 'accepted_at' => '2026-10-26 01:02:00']);
        $item = ['request_id' => $requestId, 'variant_id' => $variant, 'quantity' => 2, 'product_name' => 'Original coffee', 'variant_name' => 'M', 'unit_price' => '30000.00'];
        $itemId = DB::table('online_order_request_items')->insertGetId($item);
        foreach ([['request_id' => 999999], ['variant_id' => 999999], ['quantity' => 0], ['quantity' => -1], ['unit_price' => '-0.01'], ['unit_price' => '10000000000.00']] as $invalid) {
            $this->rejected(fn () => DB::table('online_order_request_items')->insert(array_replace($item, $invalid)));
        }
        $extra = ['request_item_id' => $itemId, 'topping_id' => $topping, 'quantity' => 2, 'topping_name' => 'Original milk', 'price' => '5000.00'];
        DB::table('online_order_request_item_toppings')->insert($extra);
        foreach ([[], ['request_item_id' => 999999], ['topping_id' => 999999], ['quantity' => 0], ['price' => '-0.01']] as $invalid) {
            $this->rejected(fn () => DB::table('online_order_request_item_toppings')->insert(array_replace($extra, $invalid)));
        }
        DB::table('products')->where('id', $product)->update(['name' => 'Renamed']);
        DB::table('product_variants')->where('id', $variant)->update(['price' => '40000.00']);
        $this->assertSame('Original coffee', DB::table('online_order_request_items')->value('product_name'));
        $this->assertSame('30000.00', DB::table('online_order_request_items')->value('unit_price'));
        $onlineOrder = array_replace($legacyOrder, ['order_code' => 'ONLINE-ORDER', 'purchase_type' => 'online', 'online_request_id' => $requestId]);
        DB::table('orders')->insert($onlineOrder);
        foreach ([['order_code' => 'DUPLICATE'], ['order_code' => 'BAD-LINK', 'online_request_id' => 999999], ['order_code' => 'BAD-TYPE', 'online_request_id' => $scheduledId, 'purchase_type' => 'counter'], ['order_code' => 'BAD-SERVICE', 'online_request_id' => $scheduledId, 'purchase_type' => 'scheduled', 'order_type' => 'dine_in'], ['order_code' => 'MISSING-LINK', 'online_request_id' => null]] as $invalid) {
            $this->rejected(fn () => DB::table('orders')->insert(array_replace($onlineOrder, $invalid)));
        }
        $this->rejected(fn () => DB::table('online_order_requests')->where('id', $requestId)->delete());
        $this->rejected(fn () => DB::table('product_variants')->where('id', $variant)->delete());
        $this->rejected(fn () => DB::table('online_order_request_items')->where('id', $itemId)->delete());
        $lastMigration = require database_path('migrations/2026_10_26_000004_add_online_purchase_fields_to_orders_table.php');
        try {
            $lastMigration->down();
            $this->fail('Rollback must refuse to delete online history.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('Refusing rollback', $exception->getMessage());
        }
        $this->assertTrue(Schema::hasColumn('orders', 'online_request_id'));
        $this->assertSame(2, DB::table('online_order_requests')->count());
        $this->assertSame(2, DB::table('orders')->count());
        $this->assertEquals($legacyPayment, DB::table('payments')->first());
    }
}
