<?php

namespace Tests\Feature;

use App\Models\Ingredient;
use App\Models\InventoryTransaction;
use App\Models\User;
use App\Services\InventoryService;
use DomainException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class InventoryServiceTest extends TestCase
{
    private InventoryService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame('sqlite', DB::connection()->getDriverName());
        $this->assertSame(':memory:', DB::connection()->getDatabaseName());

        foreach (['0001_01_01_000000_create_users_table.php', '2026_08_14_000005_create_ingredients_table.php'] as $migration) {
            (require database_path('migrations/'.$migration))->up();
        }
        $queries = DB::pretend(fn () => (require database_path('migrations/2026_08_16_000022_create_inventory_transactions_table.php'))->up());
        foreach ($queries as $query) {
            if (! str_starts_with(strtoupper(ltrim($query['query'])), 'ALTER TABLE')) {
                DB::statement($query['query'], $query['bindings']);
            }
        }

        $this->service = new InventoryService;
    }

    public function test_import_updates_stock_and_writes_an_immutable_ledger_row(): void
    {
        $ingredient = $this->ingredient(['current_stock' => '1.250']);
        $actor = $this->admin();

        $transaction = $this->service->recordImport($ingredient, $actor, '2.375', 'Nhập hàng đầu ngày');

        $this->assertSame('3.625', $ingredient->fresh()->current_stock);
        $this->assertSame('import', $transaction->type);
        $this->assertSame('2.375', $transaction->quantity);
        $this->assertSame('1.250', $transaction->before_quantity);
        $this->assertSame('3.625', $transaction->after_quantity);
        $this->assertSame($actor->id, $transaction->created_by);
        $this->assertNull($transaction->reference_type);
        $this->assertNull($transaction->reference_id);
        $this->assertSame('Nhập hàng đầu ngày', $transaction->note);
    }

    public function test_import_rejects_invalid_precision_zero_negative_and_schema_overflow(): void
    {
        $ingredient = $this->ingredient();
        $actor = $this->admin();

        foreach (['0', '-1', '1.0001', 'bad', '1000000000.000'] as $quantity) {
            try {
                $this->service->recordImport($ingredient, $actor, $quantity);
                $this->fail('Invalid import quantity was accepted: '.$quantity);
            } catch (DomainException) {
                $this->assertSame('0.000', $ingredient->fresh()->current_stock);
                $this->assertDatabaseCount('inventory_transactions', 0);
            }
        }
    }

    public function test_import_rolls_back_stock_when_ledger_write_fails(): void
    {
        $ingredient = $this->ingredient(['current_stock' => '3.000']);
        $actor = $this->admin();
        DB::statement("CREATE TRIGGER reject_inventory_insert BEFORE INSERT ON inventory_transactions BEGIN SELECT RAISE(FAIL, 'forced failure'); END");

        try {
            $this->service->recordImport($ingredient, $actor, '2.000');
            $this->fail('Expected inventory ledger write to fail.');
        } catch (QueryException) {
            $this->assertSame('3.000', $ingredient->fresh()->current_stock);
            $this->assertDatabaseCount('inventory_transactions', 0);
        }
    }

    public function test_only_active_admin_can_record_an_import(): void
    {
        $ingredient = $this->ingredient();
        foreach ([$this->user('staff', 'active'), $this->user('admin', 'inactive')] as $actor) {
            try {
                $this->service->recordImport($ingredient, $actor, '1.000');
                $this->fail('Unauthorized actor was allowed to import stock.');
            } catch (DomainException) {
                $this->assertSame('0.000', $ingredient->fresh()->current_stock);
                $this->assertDatabaseCount('inventory_transactions', 0);
            }
        }
    }

    public function test_history_is_read_only_scoped_to_ingredient_and_newest_first(): void
    {
        $ingredient = $this->ingredient();
        $other = $this->ingredient(['name' => 'Sữa']);
        $actor = $this->admin();
        $older = $this->service->recordImport($ingredient, $actor, '1.000');
        $older->forceFill(['created_at' => now()->subMinute(), 'updated_at' => now()->subMinute()])->save();
        $newer = $this->service->recordImport($ingredient, $actor, '2.000');
        $this->service->recordImport($other, $actor, '3.000');

        $history = $this->service->historyFor($ingredient);

        $this->assertSame([$newer->id, $older->id], $history->pluck('id')->all());
        $this->assertSame(2, $history->count());
        $this->assertSame('3.000', $ingredient->fresh()->current_stock);
    }

    private function admin(): User
    {
        return $this->user('admin', 'active');
    }

    private function user(string $role, string $status): User
    {
        return User::forceCreate([
            'name' => $role.' '.$status,
            'email' => uniqid($role.'-', true).'@example.test',
            'password' => 'test-password',
            'role' => $role,
            'status' => $status,
        ]);
    }

    private function ingredient(array $changes = []): Ingredient
    {
        return Ingredient::forceCreate(array_replace([
            'name' => 'Cà phê',
            'unit' => 'kg',
            'current_stock' => '0.000',
            'minimum_stock' => '0.000',
            'cost_price' => '0.00',
            'status' => 'active',
        ], $changes));
    }
}
