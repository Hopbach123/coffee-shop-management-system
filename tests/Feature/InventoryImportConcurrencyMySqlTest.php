<?php

namespace Tests\Feature;

use App\Models\Ingredient;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class InventoryImportConcurrencyMySqlTest extends TestCase
{
    private ?string $database = null;

    protected function setUp(): void
    {
        parent::setUp();
        if (getenv('INVENTORY_IMPORT_MYSQL_TEST') !== '1') {
            $this->markTestSkipped('Opt-in: creates/drops only a random inventory_import_test_* MySQL database.');
        }
        $connection = config('database.connections.mysql');
        $connection['url'] = null;
        $connection['database'] = 'information_schema';
        config(['database.connections.stock_admin' => $connection]);
        $database = 'inventory_import_test_'.bin2hex(random_bytes(8));
        DB::connection('stock_admin')->statement('CREATE DATABASE `'.$database.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->database = $database;
        $connection['database'] = $database;
        config(['database.default' => 'stock_test', 'database.connections.stock_test' => $connection]);
        $this->assertSame($database, DB::connection()->getDatabaseName());
        foreach (['0001_01_01_000000_create_users_table.php', '2026_08_14_000005_create_ingredients_table.php', '2026_08_16_000022_create_inventory_transactions_table.php'] as $migration) {
            (require database_path('migrations/'.$migration))->up();
        }
    }

    protected function tearDown(): void
    {
        try {
            if ($this->database !== null && preg_match('/\Ainventory_import_test_[0-9a-f]{16}\z/', $this->database)) {
                DB::purge('stock_test');
                DB::connection('stock_admin')->statement('DROP DATABASE `'.$this->database.'`');
            }
        } finally {
            parent::tearDown();
        }
    }

    private function parallelImports(Ingredient $ingredient, array $commands): array
    {
        $workers = [];
        DB::beginTransaction();
        Ingredient::query()->whereKey($ingredient->id)->lockForUpdate()->firstOrFail();
        try {
            foreach ($commands as $command) {
                $pipes = [];
                $outputFile = tempnam(sys_get_temp_dir(), 'stock-out-');
                $errorFile = tempnam(sys_get_temp_dir(), 'stock-err-');
                $process = proc_open([PHP_BINARY, base_path('tests/Support/stock-import-worker.php')], [['pipe', 'r'], ['file', $outputFile, 'w'], ['file', $errorFile, 'w']], $pipes, base_path());
                $this->assertIsResource($process);
                fwrite($pipes[0], json_encode($command + ['ingredient' => $ingredient->id, 'connection' => config('database.connections.stock_test')], JSON_THROW_ON_ERROR));
                fclose($pipes[0]);
                $workers[] = ['process' => $process, 'outputFile' => $outputFile, 'errorFile' => $errorFile, 'output' => ''];
            }
            $deadline = microtime(true) + 15;
            do {
                $ready = 0;
                foreach ($workers as &$worker) {
                    $worker['output'] = file_get_contents($worker['outputFile']);
                    if (str_contains($worker['output'], "READY\n")) {
                        $ready++;
                    }
                }
                unset($worker);
                if ($ready === count($workers)) {
                    break;
                }
                usleep(20000);
            } while (microtime(true) < $deadline);
            $this->assertSame(count($workers), $ready, 'Workers did not reach the locked import concurrently.');
            usleep(200000);
            DB::commit();
            $ids = [];
            foreach ($workers as &$worker) {
                $deadline = microtime(true) + 15;
                do {
                    $worker['output'] = file_get_contents($worker['outputFile']);
                    if (str_contains($worker['output'], '{')) {
                        break;
                    }
                    usleep(20000);
                } while (microtime(true) < $deadline);
                $lines = explode("\n", trim($worker['output']));
                $result = json_decode(end($lines), true);
                $this->assertIsArray($result, 'Worker failed without exposing connection data.');
                $ids[] = $result['id'] ?? $result['error'];
            }
            unset($worker);

            return $ids;
        } finally {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
            foreach ($workers as $worker) {
                $status = proc_get_status($worker['process']);
                if ($status['running']) {
                    proc_terminate($worker['process']);
                }
                proc_close($worker['process']);
                unlink($worker['outputFile']);
                unlink($worker['errorFile']);
            }
        }
    }

    public function test_duplicate_and_independent_concurrent_imports_preserve_stock_and_ledger(): void
    {
        $first = User::forceCreate(['name' => 'First', 'email' => 'first@example.test', 'password' => 'test-only', 'role' => 'admin', 'status' => 'active']);
        $second = User::forceCreate(['name' => 'Second', 'email' => 'second@example.test', 'password' => 'test-only', 'role' => 'admin', 'status' => 'active']);
        $ingredient = Ingredient::forceCreate(['name' => 'Concurrent fixture', 'unit' => 'kg', 'current_stock' => '1.250', 'minimum_stock' => 0, 'cost_price' => 0, 'status' => 'active']);
        $command = ['actor' => $first->id, 'key' => '123456789', 'quantity' => '2.375'];
        $ids = $this->parallelImports($ingredient, [$command, $command]);
        $this->assertSame($ids[0], $ids[1]);
        $this->assertSame('3.625', $ingredient->fresh()->current_stock);
        $this->assertSame(1, DB::table('inventory_transactions')->count());
        $ids = $this->parallelImports($ingredient, [
            ['actor' => $first->id, 'key' => '234567890', 'quantity' => '2'],
            ['actor' => $second->id, 'key' => '345678901', 'quantity' => '3'],
        ]);
        $this->assertNotSame($ids[0], $ids[1]);
        $this->assertSame('8.625', $ingredient->fresh()->current_stock);
        $this->assertSame(3, DB::table('inventory_transactions')->count());
        $stock = '1.250';
        foreach (DB::table('inventory_transactions')->orderBy('id')->get() as $movement) {
            $this->assertSame($stock, $movement->before_quantity);
            $stock = $movement->after_quantity;
        }
        $this->assertSame('8.625', $stock);
    }

    public function test_adjustment_duplicates_competing_waste_and_stale_stocktake(): void
    {
        $first = User::forceCreate(['name' => 'First', 'email' => 'first@example.test', 'password' => 'test-only', 'role' => 'admin', 'status' => 'active']);
        $second = User::forceCreate(['name' => 'Second', 'email' => 'second@example.test', 'password' => 'test-only', 'role' => 'admin', 'status' => 'active']);
        $ingredient = Ingredient::forceCreate(['name' => 'Concurrent fixture', 'unit' => 'kg', 'current_stock' => '10.000', 'minimum_stock' => 0, 'cost_price' => 0, 'status' => 'active']);
        $command = ['actor' => $first->id, 'key' => '123456789', 'quantity' => '2', 'mode' => 'decrease'];
        $ids = $this->parallelImports($ingredient, [$command, $command]);
        $this->assertIsInt($ids[0]);
        $this->assertSame($ids[0], $ids[1]);
        $this->assertSame('8.000', $ingredient->fresh()->current_stock);
        $ids = $this->parallelImports($ingredient, [
            ['actor' => $first->id, 'key' => '234567890', 'quantity' => '6', 'mode' => 'waste'],
            ['actor' => $second->id, 'key' => '345678901', 'quantity' => '6', 'mode' => 'waste'],
        ]);
        $this->assertCount(1, array_filter($ids, 'is_int'));
        $this->assertContains('domain_rejected', $ids);
        $this->assertSame('2.000', $ingredient->fresh()->current_stock);
        $ledger = (int) DB::table('inventory_transactions')->max('id');
        $ids = $this->parallelImports($ingredient, [
            ['actor' => $first->id, 'key' => '456789012', 'quantity' => '0', 'mode' => 'stocktake', 'stock' => '2.000', 'ledger' => $ledger],
            ['actor' => $second->id, 'key' => '567890123', 'quantity' => '1', 'mode' => 'stocktake', 'stock' => '2.000', 'ledger' => $ledger],
        ]);
        $this->assertCount(1, array_filter($ids, 'is_int'));
        $this->assertContains('domain_rejected', $ids);
        $this->assertSame(3, DB::table('inventory_transactions')->count());
        $stock = '10.000';
        foreach (DB::table('inventory_transactions')->orderBy('id')->get() as $movement) {
            $this->assertSame($stock, $movement->before_quantity);
            $stock = $movement->after_quantity;
        }
        $this->assertSame($stock, $ingredient->fresh()->current_stock);
    }
}
