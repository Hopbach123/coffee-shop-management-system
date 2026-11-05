<?php

namespace Tests\Feature;

use App\Models\Ingredient;
use App\Models\InventoryTransaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class StockAdjustmentWorkflowTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame('sqlite', DB::connection()->getDriverName());
        $this->assertSame(':memory:', DB::connection()->getDatabaseName());
        foreach (['0001_01_01_000000_create_users_table.php', '2026_08_14_000005_create_ingredients_table.php'] as $file) {
            (require database_path('migrations/'.$file))->up();
        }
        foreach (DB::pretend(fn () => (require database_path('migrations/2026_08_16_000022_create_inventory_transactions_table.php'))->up()) as $query) {
            if (! str_starts_with(strtoupper(ltrim($query['query'])), 'ALTER TABLE')) {
                DB::statement($query['query'], $query['bindings']);
            }
        }
        $this->withoutVite();
    }

    private function actor(string $role = 'admin', string $status = 'active'): User
    {
        return User::forceCreate(['name' => 'Test', 'email' => uniqid().'@example.test', 'password' => 'test-only', 'role' => $role, 'status' => $status]);
    }

    private function ingredient(): Ingredient
    {
        return Ingredient::forceCreate(['name' => 'Coffee', 'unit' => 'g', 'current_stock' => '10.000', 'minimum_stock' => 0, 'cost_price' => 0, 'status' => 'active']);
    }

    private function payload(Ingredient $ingredient, string $mode, string $quantity): array
    {
        $page = $this->get(route('admin.ingredients.adjustment.create', $ingredient))->assertOk();

        return ['mode' => $mode, 'quantity' => $quantity, 'note' => 'Kiểm kê / hư hỏng', 'operation_token' => $page->viewData('operationToken')];
    }

    public function test_four_operations_ledger_and_duplicate_retries(): void
    {
        $actor = $this->actor();
        $this->actingAs($actor);
        $ingredient = $this->ingredient();
        $uri = route('admin.ingredients.adjustment.store', $ingredient);
        foreach ([['increase', '2.125', '12.125', 'adjustment'], ['decrease', '1.125', '11.000', 'adjustment'], ['waste', '1', '10.000', 'waste'], ['stocktake', '0', '0.000', 'adjustment']] as [$mode, $quantity, $expected, $type]) {
            $data = $this->payload($ingredient, $mode, $quantity);
            $this->post($uri, $data + ['current_stock' => 888, 'created_by' => 999])->assertRedirect(route('admin.ingredients.history', $ingredient));
            $count = InventoryTransaction::count();
            $this->post($uri, $data)->assertRedirect();
            $this->assertSame($expected, $ingredient->fresh()->current_stock);
            $this->assertSame($count, InventoryTransaction::count());
            $this->assertSame($type, InventoryTransaction::latest('id')->first()->type);
            $this->assertSame($actor->id, InventoryTransaction::latest('id')->first()->created_by);
            $this->post($uri, array_replace($data, ['note' => 'Changed']))->assertSessionHasErrors('quantity');
        }
        $this->assertDatabaseCount('inventory_transactions', 4);
        $this->get(route('admin.ingredients.history', $ingredient))->assertOk()->assertSee('Hao hụt');
    }

    public function test_permissions_validation_and_token_binding(): void
    {
        $ingredient = $this->ingredient();
        $uri = route('admin.ingredients.adjustment.store', $ingredient);
        $this->get($uri)->assertRedirect('/login');
        $this->post($uri)->assertRedirect('/login');
        $this->actingAs($this->actor('staff'))->get($uri)->assertForbidden();
        $this->actingAs($this->actor('admin', 'inactive'))->post($uri)->assertForbidden();
        $this->actingAs($this->actor());
        $data = $this->payload($ingredient, 'decrease', '1');
        foreach (['0', '-1', '1.0001', '1000000000', '1e2', 'abc', ['bad']] as $quantity) {
            $this->post($uri, array_replace($data, ['quantity' => $quantity]))->assertSessionHasErrors('quantity');
        }
        $this->post($uri, array_replace($data, ['note' => '  ']))->assertSessionHasErrors('note');
        $this->post($uri, array_replace($data, ['note' => str_repeat('a', 1001)]))->assertSessionHasErrors('note');
        $this->post($uri, array_replace($data, ['mode' => 'import']))->assertSessionHasErrors('mode');
        $this->post($uri, array_replace($data, ['operation_token' => 'broken']))->assertSessionHasErrors('operation_token');
        $other = $this->ingredient();
        $this->post(route('admin.ingredients.adjustment.store', $other), $data)->assertSessionHasErrors('operation_token');
        $this->actingAs($this->actor())->post($uri, $data)->assertSessionHasErrors('operation_token');
        $this->followingRedirects()->from($uri)->post($uri, array_replace($data, ['quantity' => ['bad'], 'note' => ['bad'], 'mode' => ['bad']]))->assertOk();
        $this->assertDatabaseCount('inventory_transactions', 0);
    }

    public function test_stocktake_stale_even_when_stock_returns_to_same_value(): void
    {
        $actor = $this->actor();
        $this->actingAs($actor);
        $ingredient = $this->ingredient();
        $uri = route('admin.ingredients.adjustment.store', $ingredient);
        $stocktake = $this->payload($ingredient, 'stocktake', '8');
        $this->post($uri, $this->payload($ingredient, 'increase', '1'))->assertRedirect();
        $this->post($uri, $this->payload($ingredient, 'decrease', '1'))->assertRedirect();
        $this->post($uri, $stocktake)->assertSessionHasErrors('quantity');
        $this->assertSame('10.000', $ingredient->fresh()->current_stock);
        $this->post($uri, $this->payload($ingredient, 'stocktake', '10'))->assertSessionHasErrors('quantity');
        $this->post($uri, $this->payload($ingredient, 'waste', '10.001'))->assertSessionHasErrors('quantity');
        $ingredient->forceFill(['current_stock' => '999999999.999'])->save();
        $this->post($uri, $this->payload($ingredient, 'increase', '0.001'))->assertSessionHasErrors('quantity');
        $this->assertDatabaseCount('inventory_transactions', 2);
    }

    public function test_rollback_unit_status_deleted_and_csrf(): void
    {
        $this->actingAs($this->actor());
        $ingredient = $this->ingredient();
        $uri = route('admin.ingredients.adjustment.store', $ingredient);
        $data = $this->payload($ingredient, 'decrease', '2');
        InventoryTransaction::saving(fn () => false);
        try {
            $this->postJson($uri, $data)->assertStatus(500);
        } finally {
            InventoryTransaction::flushEventListeners();
        }
        $this->assertSame('10.000', $ingredient->fresh()->current_stock);
        $this->assertDatabaseCount('inventory_transactions', 0);
        $ingredient->forceFill(['unit' => 'kg'])->save();
        $this->post($uri, $data)->assertSessionHasErrors('quantity');
        $ingredient->forceFill(['unit' => 'g', 'status' => 'inactive'])->save();
        $this->post($uri, $data)->assertSessionHasErrors('quantity');
        $this->get($uri)->assertStatus(422);
        $ingredient->forceFill(['status' => 'active'])->save();
        $this->post($uri, $data)->assertRedirect();
        $this->assertSame('8.000', $ingredient->fresh()->current_stock);
        $this->app->instance('env', 'local');
        $this->post($uri, $data)->assertStatus(419);
        $ingredient->delete();
        $this->get($uri)->assertNotFound();
    }
}
