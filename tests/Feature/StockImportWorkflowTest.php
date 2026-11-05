<?php

namespace Tests\Feature;

use App\Models\Ingredient;
use App\Models\InventoryTransaction;
use App\Models\User;
use App\Services\InventoryService;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class StockImportWorkflowTest extends TestCase
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

    private function token(Ingredient $ingredient, User $actor, string $key = '123456'): string
    {
        return Crypt::encryptString(json_encode([
            'key' => $key, 'ingredient' => $ingredient->id, 'actor' => $actor->id, 'unit' => $ingredient->unit,
        ], JSON_THROW_ON_ERROR));
    }

    public function test_admin_can_import_and_retry_without_double_stock(): void
    {
        $actor = $this->admin();
        $ingredient = $this->ingredient(['current_stock' => '1.250']);
        $this->withoutVite()->actingAs($actor);
        $uri = '/admin/ingredients/'.$ingredient->id.'/import';
        $this->get($uri)->assertOk()->assertSee('operation_token')->assertSee('kg');
        $data = ['quantity' => '2.375', 'note' => 'Receipt test', 'operation_token' => $this->token($ingredient, $actor), 'current_stock' => 999, 'created_by' => 999];
        $this->post($uri, $data)->assertRedirect(route('admin.ingredients.history', $ingredient));
        $this->post($uri, $data)->assertRedirect();
        $this->assertSame('3.625', $ingredient->fresh()->current_stock);
        $this->assertDatabaseCount('inventory_transactions', 1);
        $this->assertDatabaseHas('inventory_transactions', ['created_by' => $actor->id, 'type' => 'import', 'reference_type' => 'stock_import', 'quantity' => 2.375]);
        $this->get(route('admin.ingredients.history', $ingredient))->assertOk()->assertSee('Receipt test');
        $data['quantity'] = '3';
        $this->post($uri, $data)->assertSessionHasErrors('quantity');
        $this->assertSame('3.625', $ingredient->fresh()->current_stock);
    }

    public function test_validation_permissions_token_scope_unit_and_overflow(): void
    {
        $actor = $this->admin();
        $ingredient = $this->ingredient();
        $uri = '/admin/ingredients/'.$ingredient->id.'/import';
        $this->get($uri)->assertRedirect('/login');
        $this->post($uri)->assertRedirect('/login');
        $this->actingAs($this->user('staff', 'active'))->get($uri)->assertForbidden();
        $this->actingAs($this->user('admin', 'inactive'))->post($uri)->assertForbidden();
        $this->actingAs($actor)->withoutVite();
        $token = $this->token($ingredient, $actor);
        foreach (['0', '-1', '1.0001', '1000000000', 'abc', ['bad'], '999999999999999999999999999999999'] as $quantity) {
            $this->from($uri)->post($uri, ['quantity' => $quantity, 'operation_token' => $token])->assertSessionHasErrors('quantity');
        }
        $this->followingRedirects()->from($uri)->post($uri, ['quantity' => ['bad'], 'note' => ['bad'], 'operation_token' => $token])->assertOk()->assertSee('aria-invalid', false);
        $this->post($uri, ['quantity' => '1', 'operation_token' => 'tampered'])->assertSessionHasErrors('operation_token');
        $other = $this->ingredient();
        $this->post('/admin/ingredients/'.$other->id.'/import', ['quantity' => '1', 'operation_token' => $token])->assertSessionHasErrors('operation_token');
        $ingredient->forceFill(['unit' => 'g'])->save();
        $this->post($uri, ['quantity' => '1', 'operation_token' => $token])->assertSessionHasErrors('quantity');
        $ingredient->forceFill(['unit' => 'kg', 'current_stock' => '999999999.999'])->save();
        $this->post($uri, ['quantity' => '0.001', 'operation_token' => $token])->assertSessionHasErrors('quantity');
        $this->assertDatabaseCount('inventory_transactions', 0);
        $ingredient->forceFill(['status' => 'inactive'])->save();
        $this->post($uri, ['quantity' => '1', 'operation_token' => $token])->assertSessionHasErrors('quantity');
        $ingredient->delete();
        $this->get($uri)->assertNotFound();
    }

    public function test_http_failure_rolls_back_and_same_token_can_be_retried(): void
    {
        $actor = $this->admin();
        $ingredient = $this->ingredient(['current_stock' => '3.000']);
        $this->actingAs($actor);
        $uri = '/admin/ingredients/'.$ingredient->id.'/import';
        $data = ['quantity' => '2', 'operation_token' => $this->token($ingredient, $actor)];
        InventoryTransaction::saving(fn () => false);
        try {
            $this->postJson($uri, $data)->assertStatus(500);
        } finally {
            InventoryTransaction::flushEventListeners();
        }
        $this->assertSame('3.000', $ingredient->fresh()->current_stock);
        $this->assertDatabaseCount('inventory_transactions', 0);
        $this->post($uri, $data)->assertRedirect();
        $this->assertSame('5.000', $ingredient->fresh()->current_stock);
        $this->app->instance('env', 'local');
        $this->post($uri, $data)->assertStatus(419);
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
