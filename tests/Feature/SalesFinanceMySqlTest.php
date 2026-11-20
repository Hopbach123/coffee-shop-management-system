<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\CafeTable;
use App\Models\Category;
use App\Models\Ingredient;
use App\Models\OnlineOrderRequest;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductRecipe;
use App\Models\ProductVariant;
use App\Models\Promotion;
use App\Models\Reservation;
use App\Models\User;
use App\Services\OnlineRequestSubmissionService;
use App\Services\OrderService;
use App\Services\PaymentService;
use App\Services\ScheduledPickupService;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SalesFinanceMySqlTest extends TestCase
{
    private ?string $createdDatabase = null;

    private User $staff;

    private User $other;

    private ProductVariant $variant;

    protected function setUp(): void
    {
        parent::setUp();
        if (getenv('SALES_FINANCE_MYSQL_TEST') !== '1') {
            $this->markTestSkipped('Opt-in: requires isolated localhost instance; creates only a new random sales_finance_test_* DB.');
        }
        $port = getenv('SALES_FINANCE_TEST_PORT');
        $this->assertNotFalse($port, 'Explicit isolated instance port required.');
        $connection = config('database.connections.mysql');
        $connection = array_replace($connection, ['url' => null, 'host' => '127.0.0.1', 'port' => $port,
            'database' => 'information_schema', 'username' => getenv('SALES_FINANCE_TEST_USER') ?: 'root',
            'password' => getenv('SALES_FINANCE_TEST_PASSWORD') ?: '']);
        config(['database.connections.sales_qa_admin' => $connection]);
        $name = 'sales_finance_test_'.bin2hex(random_bytes(8));
        DB::connection('sales_qa_admin')->statement('CREATE DATABASE `'.$name.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->createdDatabase = $name;
        $connection['database'] = $name;
        config(['database.default' => 'sales_qa', 'database.connections.sales_qa' => $connection]);
        $this->assertSame($name, DB::connection()->getDatabaseName());
        $this->assertSame([], DB::select('SHOW TABLES'));
        $this->assertSame(0, Artisan::call('migrate', ['--database' => 'sales_qa', '--force' => true]));
        $this->assertSame(0, Artisan::call('migrate:status', ['--database' => 'sales_qa']));
        $this->staff = $this->actor('first');
        $this->other = $this->actor('second');
        $category = Category::forceCreate(['name' => 'QA Coffee', 'status' => 'active']);
        $product = Product::forceCreate(['category_id' => $category->id, 'name' => 'QA Coffee', 'slug' => 'qa-coffee', 'status' => 'available']);
        $this->variant = ProductVariant::forceCreate(['product_id' => $product->id, 'name' => 'M', 'price' => '35.50', 'status' => 'available']);
    }

    protected function tearDown(): void
    {
        try {
            if ($this->createdDatabase && preg_match('/\Asales_finance_test_[0-9a-f]{16}\z/', $this->createdDatabase)) {
                DB::purge('sales_qa');
                DB::connection('sales_qa_admin')->statement('DROP DATABASE `'.$this->createdDatabase.'`');
            }
        } finally {
            parent::tearDown();
        }
    }

    private function actor(string $label): User
    {
        return User::forceCreate(['name' => $label, 'email' => $label.'@example.test', 'password' => 'test-only', 'role' => 'staff', 'status' => 'active']);
    }

    private function order(): Order
    {
        return app(OrderService::class)->createWithItems(['order_type' => 'takeaway'], [['variant_id' => $this->variant->id, 'quantity' => 1]], $this->staff);
    }

    private function pay(Order $order, User $actor, array $overrides = []): array
    {
        return ['command' => 'pay', 'order' => $order->id, 'actor' => $actor->id,
            'payload' => array_replace(['payment_method' => 'cash', 'received_amount' => '50.00', 'received_confirmation' => true, 'operation_key' => bin2hex(random_bytes(16))], $overrides)];
    }

    /** Two independent PHP/PDO processes, synchronized at a shared start barrier. */
    private function race(string $case, array $commands, ?Order $blockOrder = null): array
    {
        $workers = [];
        $barrier = tempnam(sys_get_temp_dir(), 'sales-qa-go-');
        unlink($barrier);
        if ($blockOrder) {
            DB::beginTransaction();
            Order::whereKey($blockOrder->id)->lockForUpdate()->firstOrFail();
        }
        try {
            foreach ($commands as $command) {
                $out = tempnam(sys_get_temp_dir(), 'sales-qa-out-');
                $err = tempnam(sys_get_temp_dir(), 'sales-qa-err-');
                $process = proc_open([PHP_BINARY, base_path('tests/Support/sales-finance-worker.php')], [['pipe', 'r'], ['file', $out, 'w'], ['file', $err, 'w']], $pipes, base_path());
                $this->assertIsResource($process);
                fwrite($pipes[0], json_encode($command + ['connection' => config('database.connections.sales_qa'), 'barrier' => $barrier], JSON_THROW_ON_ERROR));
                fclose($pipes[0]);
                $workers[] = compact('process', 'out', 'err');
            }
            $deadline = microtime(true) + 20;
            do {
                $ready = count(array_filter($workers, fn ($w) => str_contains(file_get_contents($w['out']), "READY\n")));
                if ($ready === count($workers)) {
                    break;
                }
                usleep(10000);
            } while (microtime(true) < $deadline);
            $this->assertSame(count($workers), $ready, 'Workers must be ready concurrently.');
            touch($barrier);
            if ($blockOrder) {
                usleep(200000); // Both commands contend on an actual Order row lock.
                DB::commit();
            }
            $results = [];
            foreach ($workers as $w) {
                $deadline = microtime(true) + 25;
                do {
                    $lines = explode("\n", trim(file_get_contents($w['out'])));
                    $result = json_decode(end($lines), true);
                    if (is_array($result)) {
                        break;
                    }
                    usleep(10000);
                } while (microtime(true) < $deadline);
                $this->assertIsArray($result, 'No worker outcome (connection details suppressed).');
                $this->assertNotSame('error', $result['outcome'], json_encode($result));
                $results[] = $result;
            }
            file_put_contents(storage_path('logs/sales-concurrency-evidence.jsonl'), json_encode([
                'case' => $case, 'database' => $this->createdDatabase, 'outcomes' => $results,
                'payments' => Payment::count(), 'paid' => Payment::where('status', 'paid')->count(),
                'orders' => Order::orderBy('id')->get(['id', 'status', 'total_amount'])->toArray(),
                'ledger_rows' => DB::table('inventory_transactions')->count(),
            ], JSON_THROW_ON_ERROR)."\n", FILE_APPEND);

            return $results;
        } finally {
            if (DB::transactionLevel()) {
                DB::rollBack();
            }
            foreach ($workers as $w) {
                if (proc_get_status($w['process'])['running']) {
                    proc_terminate($w['process']);
                }
                proc_close($w['process']);
                unlink($w['out']);
                unlink($w['err']);
            }
            if (is_file($barrier)) {
                unlink($barrier);
            }
        }
    }

    public function test_cash_bank_same_order_and_operation_replay_conflict(): void
    {
        foreach (['cash', 'bank_transfer'] as $method) {
            $order = $this->order();
            $overrides = ['payment_method' => $method, 'received_amount' => '35.50', 'transaction_code' => 'QA-'.$method];
            $results = $this->race($method === 'cash' ? 'C01' : 'C02', [$this->pay($order, $this->staff, $overrides), $this->pay($order, $this->other, $overrides)], $order);
            $this->assertCount(1, array_filter($results, fn ($r) => $r['outcome'] === 'success'));
            $this->assertSame(1, $order->payments()->count());
        }
        $order = $this->order();
        $command = $this->pay($order, $this->staff);
        $results = $this->race('C03', [$command, $command], $order);
        $this->assertSame($results[0]['id'], $results[1]['id']);
        $order = $this->order();
        $command = $this->pay($order, $this->staff);
        $other = $command;
        $other['payload']['received_amount'] = '60.00';
        $results = $this->race('C04', [$command, $other], $order);
        $this->assertCount(1, array_filter($results, fn ($r) => $r['outcome'] === 'success'));
        $this->assertSame(1, $order->payments()->count());
    }

    public function test_bank_reference_is_unique_across_two_orders(): void
    {
        $one = $this->order();
        $two = $this->order();
        $attrs = ['payment_method' => 'bank_transfer', 'received_amount' => '35.50', 'transaction_code' => 'SAME-REFERENCE'];
        $results = $this->race('C05', [$this->pay($one, $this->staff, $attrs), $this->pay($two, $this->other, $attrs)]);
        $this->assertCount(1, array_filter($results, fn ($r) => $r['outcome'] === 'success'));
        $this->assertSame(1, Payment::count());
    }

    public function test_payment_races_cancel_serve_complete_and_promotion(): void
    {
        $order = $this->order();
        $this->race('C06', [$this->pay($order, $this->staff), ['command' => 'cancel', 'actor' => $this->other->id, 'order' => $order->id]], $order);
        $this->assertFalse($order->fresh()->status === 'cancelled' && $order->payments()->exists());
        $order = $this->order();
        $item = $order->orderItems()->firstOrFail();
        $item->forceFill(['status' => 'ready'])->save();
        $this->race('C07', [$this->pay($order, $this->staff), ['command' => 'serve', 'actor' => $this->other->id, 'order' => $order->id, 'item' => $item->id]], $order);
        $this->assertSame(1, $order->payments()->count());
        $this->assertContains($item->fresh()->status, ['ready', 'served']);
        $order = $this->order();
        $order->orderItems()->update(['status' => 'served']);
        $this->race('C08', [$this->pay($order, $this->staff), ['command' => 'complete', 'actor' => $this->other->id, 'order' => $order->id]], $order);
        $this->assertFalse($order->fresh()->status === 'completed' && ! $order->isPaidInFull());
        $promo = Promotion::forceCreate(['code' => 'RACE', 'name' => 'QA', 'discount_type' => 'fixed', 'discount_value' => '5.00', 'minimum_order' => '0.00', 'start_at' => now()->subDay(), 'end_at' => now()->addDay(), 'status' => 'active']);
        $order = $this->order();
        $this->race('C09', [$this->pay($order, $this->staff), ['command' => 'promote', 'actor' => $this->other->id, 'order' => $order->id, 'code' => $promo->code]], $order);
        $this->assertSame((string) $order->fresh()->total_amount, (string) $order->payments()->firstOrFail()->amount);
    }

    public function test_inventory_last_stock_accept_and_submission_concurrently(): void
    {
        $ingredient = Ingredient::forceCreate(['name' => 'Last beans', 'unit' => 'g', 'current_stock' => '20.000', 'status' => 'active']);
        ProductRecipe::forceCreate(['variant_id' => $this->variant->id, 'ingredient_id' => $ingredient->id, 'quantity' => '20.000']);
        $one = $this->order();
        $two = $this->order();
        $results = $this->race('C10', [['command' => 'prepare', 'actor' => $this->staff->id, 'order' => $one->id, 'item' => $one->orderItems()->first()->id], ['command' => 'prepare', 'actor' => $this->other->id, 'order' => $two->id, 'item' => $two->orderItems()->first()->id]]);
        $this->assertCount(1, array_filter($results, fn ($r) => $r['outcome'] === 'success'));
        $this->assertSame('0.000', $ingredient->fresh()->current_stock);
        $this->assertSame(1, DB::table('inventory_transactions')->count());
        $attributes = ['request_code' => 'QA-REQUEST', 'purchase_type' => 'online', 'contact_name' => 'QA Guest', 'contact_phone' => '0900000000', 'payment_preference' => 'cash', 'payment_timing' => 'on_receipt', 'status' => 'pending', 'submission_key' => bin2hex(random_bytes(32)), 'tracking_token_hash' => hash('sha256', 'qa-test-only')];
        $items = [['variant_id' => $this->variant->id, 'quantity' => 1, 'note' => null, 'product_name' => 'QA Coffee', 'variant_name' => 'M', 'unit_price' => '35.50', 'toppings' => []]];
        $plainToken = bin2hex(random_bytes(32));
        $attributes['tracking_token_hash'] = hash('sha256', $plainToken);
        $command = ['command' => 'submit_http', 'actor' => $this->staff->id, 'attributes' => $attributes, 'items' => $items, 'variant' => $this->variant->id, 'plain_token' => $plainToken];
        $results = $this->race('C12', [$command, $command]);
        $this->assertSame($results[0]['id'], $results[1]['id']);
        $request = OnlineOrderRequest::firstOrFail();
        $results = $this->race('C11', [['command' => 'accept', 'actor' => $this->staff->id, 'request' => $request->id], ['command' => 'accept', 'actor' => $this->other->id, 'request' => $request->id]]);
        $this->assertSame($results[0]['id'], $results[1]['id']);
        $this->assertSame(1, Order::where('online_request_id', $request->id)->count());
        $this->assertSame(1, DB::table('inventory_transactions')->count(), 'Accept must not consume.');
    }

    public function test_mysql_upgrade_preserves_legacy_data_and_enforces_receipt_constraints(): void
    {
        $migration = require database_path('migrations/2026_11_17_000001_add_payment_receipt_contract.php');
        $migration->down();
        $order = $this->order();
        $legacy = Payment::forceCreate(['order_id' => $order->id, 'payment_code' => 'LEGACY', 'payment_method' => 'bank_transfer', 'amount' => '35.50', 'transaction_code' => 'bank:legacy-001', 'status' => 'paid', 'paid_at' => now(), 'created_by' => $this->staff->id]);
        $before = DB::table('payments')->where('id', $legacy->id)->first();
        $migration->up();
        $after = DB::table('payments')->where('id', $legacy->id)->first();
        foreach ((array) $before as $column => $value) {
            $this->assertSame($value, $after->$column, 'Legacy column changed: '.$column);
        }
        $this->assertNull($after->received_amount);
        $this->assertNull($after->operation_key);
        $this->assertSame(hash('sha256', 'BANK:LEGACY-001'), $after->bank_reference_key);
        $column = DB::selectOne("SHOW COLUMNS FROM payments LIKE 'paid_order_key'");
        $this->assertStringContainsString('VIRTUAL GENERATED', $column->Extra);
        try {
            Payment::forceCreate(['order_id' => $order->id, 'payment_code' => 'DUPLICATE', 'payment_method' => 'cash', 'amount' => '35.50', 'status' => 'paid', 'paid_at' => now(), 'created_by' => $this->staff->id]);
            $this->fail('Unique paid order must reject duplicate.');
        } catch (UniqueConstraintViolationException) {
            $this->assertSame(1, Payment::count());
        }
        foreach (['PENDING-ONE', 'PENDING-TWO'] as $code) {
            Payment::forceCreate(['order_id' => $order->id, 'payment_code' => $code, 'payment_method' => 'cash', 'amount' => '35.50', 'status' => 'pending', 'created_by' => $this->staff->id]);
        }
        $this->assertSame(2, Payment::whereNull('paid_order_key')->count());
        $migration->down();
        // Duplicate legacy paid rows must stop upgrade before new columns are added.
        Payment::forceCreate(['order_id' => $order->id, 'payment_code' => 'OLD-DUPLICATE', 'payment_method' => 'cash', 'amount' => '35.50', 'status' => 'paid', 'paid_at' => now(), 'created_by' => $this->staff->id]);
        try {
            $migration->up();
            $this->fail('Preflight must reject duplicate paid history.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('Duplicate paid', $exception->getMessage());
        }
        $this->assertFalse(Schema::hasColumn('payments', 'operation_key'));
    }

    public function test_counter_online_scheduled_complete_on_real_mysql_services(): void
    {
        // Fixture hours apply only to this isolated test, not the store configuration.
        config(['shop.opening_time' => '08:00', 'shop.closing_time' => '20:00']);
        $ingredient = Ingredient::forceCreate(['name' => 'Flow beans', 'unit' => 'g', 'current_stock' => '100.000', 'status' => 'active']);
        ProductRecipe::forceCreate(['variant_id' => $this->variant->id, 'ingredient_id' => $ingredient->id, 'quantity' => '20.000']);
        $sales = app(OrderService::class);
        foreach (['counter', 'online', 'scheduled'] as $type) {
            $request = null;
            if ($type === 'counter') {
                $order = $sales->createPosOnce(['order_type' => 'takeaway'], [['variant_id' => $this->variant->id, 'quantity' => 1]], $this->staff, 'mysql-flow-counter');
            } else {
                $pickup = $type === 'scheduled' ? app(ScheduledPickupService::class)->validateTime(now('Asia/Ho_Chi_Minh')->addDay()->setTime(12, 0)->format('Y-m-d\TH:i'))->format('Y-m-d H:i:s') : null;
                $request = app(OnlineRequestSubmissionService::class)->submit([
                    'request_code' => 'FLOW-'.$type, 'purchase_type' => $type, 'contact_name' => 'Flow Customer', 'contact_phone' => '0900000000',
                    'pickup_at' => $pickup, 'payment_preference' => 'bank_transfer', 'payment_timing' => 'prepaid', 'status' => 'pending',
                    'submission_key' => hash('sha256', $type), 'tracking_token_hash' => hash('sha256', 'test-flow-'.$type),
                ], [['variant_id' => $this->variant->id, 'quantity' => 1, 'note' => null, 'product_name' => 'QA Coffee', 'variant_name' => 'M', 'unit_price' => '35.50', 'toppings' => []]]);
                $before = $ingredient->fresh()->current_stock;
                $order = $sales->acceptOnlineRequest($request, $this->staff);
                $this->assertSame($before, $ingredient->fresh()->current_stock);
            }
            $item = $order->orderItems()->firstOrFail();
            if ($type === 'counter') {
                $sales->transitionItemStatus($order, $item, 'preparing', $this->staff);
                $sales->transitionItemStatus($order, $item, 'ready', $this->staff);
            }
            $ledgerBefore = DB::table('inventory_transactions')->count();
            $sales->recordPayment($order, $this->pay($order, $this->staff, ['payment_method' => $type === 'counter' ? 'cash' : 'bank_transfer', 'received_amount' => $type === 'counter' ? '50.00' : '35.50', 'transaction_code' => 'FLOW-BANK-'.$type])['payload'], $this->staff);
            $this->assertSame($ledgerBefore, DB::table('inventory_transactions')->count());
            $this->assertSame('pending', $order->fresh()->status);
            if ($type !== 'counter') {
                $this->assertSame('pending', $item->fresh()->status, 'Paid must not prepare scheduled/online.');
                $sales->transitionItemStatus($order, $item, 'preparing', $this->staff);
                $sales->transitionItemStatus($order, $item, 'ready', $this->staff);
            }
            $sales->transitionItemStatus($order, $item, 'served', $this->staff, $request?->request_code);
            $this->assertSame('completed', $sales->complete($order, $this->staff)->status);
            $this->assertSame('paid', app(PaymentService::class)->settlementForOrder($order)['payment_status']);
        }
        $this->assertSame('40.000', $ingredient->fresh()->current_stock);
        $this->assertSame(3, DB::table('inventory_transactions')->count());
        $this->assertSame(3, Payment::where('status', 'paid')->count());
    }

    public function test_completion_uses_current_settlement_after_older_repeatable_read_snapshot(): void
    {
        $order = $this->order();
        $order->orderItems()->update(['status' => 'served']);
        $results = $this->race('C08-current-read', [$this->pay($order, $this->staff), [
            'command' => 'stale_complete', 'actor' => $this->other->id, 'order' => $order->id,
        ]]);
        $this->assertSame(['success', 'success'], array_column($results, 'outcome'));
        $this->assertSame('completed', $order->fresh()->status);
    }

    public function test_dine_in_completion_locks_table_before_order_and_races_reservation_cancel(): void
    {
        $area = Area::forceCreate(['name' => 'QA Area', 'status' => 'active']);
        $table = CafeTable::forceCreate(['area_id' => $area->id, 'table_code' => 'QA-1', 'capacity' => 4, 'status' => 'available']);
        $sales = app(OrderService::class);
        $order = $sales->createWithItems(['order_type' => 'dine_in', 'table_id' => $table->id], [['variant_id' => $this->variant->id, 'quantity' => 1]], $this->staff);
        $reservation = Reservation::forceCreate(['table_id' => $table->id, 'customer_name' => 'QA Guest', 'customer_phone' => '0900000000', 'guest_count' => 1, 'reservation_start_at' => now(), 'reservation_end_at' => now()->addHour(), 'status' => 'confirmed']);
        $order->forceFill(['reservation_id' => $reservation->id])->save();
        $sales->recordPayment($order, $this->pay($order, $this->staff)['payload'], $this->staff);
        $order->orderItems()->update(['status' => 'served']);
        $results = $this->race('TABLE-ORDER-lock-regression', [
            ['command' => 'complete', 'order' => $order->id, 'actor' => $this->staff->id],
            ['command' => 'reservation_cancel', 'reservation' => $reservation->id, 'actor' => $this->other->id],
        ]);
        $this->assertSame('success', $results[0]['outcome']);
        $this->assertSame('rejected', $results[1]['outcome']);
        $this->assertSame('completed', $order->fresh()->status);
        $this->assertSame('confirmed', $reservation->fresh()->status);
        $this->assertSame('available', $table->fresh()->status);
    }
}
