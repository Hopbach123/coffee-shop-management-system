<?php

namespace Tests\Feature;

use App\Models\Ingredient;
use App\Models\ProductRecipe;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ProductRecipeManagementTest extends TestCase
{
    private ProductVariant $variant;

    private Ingredient $ingredient;

    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame('sqlite', DB::connection()->getDriverName());
        $this->assertSame(':memory:', DB::connection()->getDatabaseName());
        foreach (['0001_01_01_000000_create_users_table.php', '2026_08_14_000003_create_categories_table.php', '2026_08_14_000005_create_ingredients_table.php', '2026_08_14_000007_create_products_table.php', '2026_08_16_000014_create_product_variants_table.php', '2026_08_16_000021_create_product_recipes_table.php', '2026_08_16_000022_create_inventory_transactions_table.php'] as $file) {
            foreach (DB::pretend(fn () => (require database_path('migrations/'.$file))->up()) as $query) {
                if (! str_starts_with(strtoupper(ltrim($query['query'])), 'ALTER TABLE')) {
                    DB::statement($query['query'], $query['bindings']);
                }
            }
        }
        $category = DB::table('categories')->insertGetId(['name' => 'Coffee']);
        $product = DB::table('products')->insertGetId(['category_id' => $category, 'name' => 'Coffee', 'slug' => 'coffee']);
        $this->variant = ProductVariant::forceCreate(['product_id' => $product, 'name' => 'M', 'price' => 30000, 'status' => 'available']);
        $this->ingredient = Ingredient::forceCreate(['name' => 'Cà phê hạt', 'unit' => 'g', 'current_stock' => '1000.000', 'minimum_stock' => 10, 'cost_price' => 200, 'status' => 'active']);
        $this->withoutVite();
    }

    private function login(string $role = 'admin'): void
    {
        $this->actingAs(User::forceCreate(['name' => 'Recipe tester', 'email' => $role.'@example.test', 'password' => 'test-password', 'role' => $role, 'status' => 'active']), 'web');
    }

    private function uri(?ProductRecipe $recipe = null): string
    {
        return '/admin/product-variants/'.$this->variant->id.'/recipes'.($recipe ? '/'.$recipe->id : '');
    }

    private function recipe(): ProductRecipe
    {
        return $this->variant->productRecipes()->create(['ingredient_id' => $this->ingredient->id, 'quantity' => '18.500']);
    }

    public function test_guest_and_staff_cannot_access_any_recipe_operation(): void
    {
        $recipe = $this->recipe();
        foreach ([['GET', $this->uri()], ['POST', $this->uri()], ['PUT', $this->uri($recipe)], ['DELETE', $this->uri($recipe)]] as [$method, $uri]) {
            $this->call($method, $uri)->assertRedirect('/login');
        }
        $this->login('staff');
        foreach ([['GET', $this->uri()], ['POST', $this->uri()], ['PUT', $this->uri($recipe)], ['DELETE', $this->uri($recipe)]] as [$method, $uri]) {
            $this->call($method, $uri)->assertForbidden();
        }
        $this->assertDatabaseHas('product_recipes', ['id' => $recipe->id, 'quantity' => 18.5]);
    }

    public function test_admin_adds_updates_and_removes_recipe_without_changing_stock_or_ledger(): void
    {
        $this->login();
        $this->get($this->uri())->assertOk()->assertSee('chưa có công thức')->assertSee('Cà phê hạt (g)');
        $this->get('/admin/product-variants')->assertOk()->assertSee($this->uri(), false);
        $this->post($this->uri(), ['ingredient_id' => $this->ingredient->id, 'quantity' => '18.500', 'variant_id' => 999, 'current_stock' => 0])->assertRedirect($this->uri())->assertSessionHasNoErrors();
        $recipe = ProductRecipe::firstOrFail();
        $this->assertSame($this->variant->id, $recipe->variant_id);
        $this->get($this->uri())->assertOk()->assertSee('18.500')->assertSee('Cà phê hạt');
        $this->put($this->uri($recipe), ['quantity' => '0.001', 'ingredient_id' => 999, 'variant_id' => 999, 'current_stock' => 0])->assertSessionHasNoErrors();
        $this->assertSame('0.001', $recipe->fresh()->quantity);
        $this->assertSame($this->ingredient->id, $recipe->fresh()->ingredient_id);
        $this->delete($this->uri($recipe))->assertRedirect($this->uri());
        $this->assertDatabaseCount('product_recipes', 0);
        $this->assertSame('1000.000', $this->ingredient->fresh()->current_stock);
        $this->assertDatabaseCount('inventory_transactions', 0);
    }

    public function test_quantity_boundaries_and_wrong_types_on_create_and_update(): void
    {
        $this->login();
        foreach ([null, '', 0, -1, '0.0001', '1.1234', '1000000000', 'abc', ['bad']] as $quantity) {
            $this->post($this->uri(), ['ingredient_id' => $this->ingredient->id, 'quantity' => $quantity])->assertSessionHasErrors('quantity');
        }
        $recipe = $this->recipe();
        foreach ([null, '', 0, -1, '0.0001', '1.1234', '1000000000', 'abc', ['bad']] as $quantity) {
            $this->put($this->uri($recipe), ['quantity' => $quantity])->assertSessionHasErrors('quantity');
        }
        $this->put($this->uri($recipe), ['quantity' => '999999999.999'])->assertSessionHasNoErrors();
        $this->assertSame('999999999.999', $recipe->fresh()->quantity);
    }

    public function test_invalid_inactive_deleted_and_duplicate_ingredients_are_rejected(): void
    {
        $this->login();
        foreach ([null, 'abc', ['bad'], 99999] as $id) {
            $this->post($this->uri(), ['ingredient_id' => $id, 'quantity' => 1])->assertSessionHasErrors('ingredient_id');
        }
        $this->ingredient->forceFill(['status' => 'inactive'])->save();
        $this->post($this->uri(), ['ingredient_id' => $this->ingredient->id, 'quantity' => 1])->assertSessionHasErrors('ingredient_id');
        $this->ingredient->forceFill(['status' => 'active'])->save();
        $recipe = $this->recipe();
        $this->post($this->uri(), ['ingredient_id' => $this->ingredient->id, 'quantity' => 1])->assertSessionHasErrors('ingredient_id');
        $this->assertDatabaseCount('product_recipes', 1);
        $this->ingredient->delete();
        $this->get($this->uri())->assertOk()->assertSee('(đã xóa)')->assertSee('Cà phê hạt');
        $this->delete($this->uri($recipe))->assertRedirect();
        $this->post($this->uri(), ['ingredient_id' => $this->ingredient->id, 'quantity' => 1])->assertSessionHasErrors('ingredient_id');
    }

    public function test_recipe_ids_are_scoped_to_the_variant_and_deleted_variants_are_not_accessible(): void
    {
        $this->login();
        $recipe = $this->recipe();
        $other = ProductVariant::forceCreate(['product_id' => $this->variant->product_id, 'name' => 'L', 'price' => 40000]);
        $uri = '/admin/product-variants/'.$other->id.'/recipes/'.$recipe->id;
        $this->put($uri, ['quantity' => 1])->assertNotFound();
        $this->delete($uri)->assertNotFound();
        $this->variant->delete();
        $this->get($this->uri())->assertNotFound();
        $this->post($this->uri(), ['ingredient_id' => $this->ingredient->id, 'quantity' => 1])->assertNotFound();
        $this->assertDatabaseHas('product_recipes', ['id' => $recipe->id, 'quantity' => 18.5]);
    }

    public function test_same_ingredient_can_be_used_in_other_variant_and_existing_history_is_preserved(): void
    {
        $this->login();
        $recipe = $this->recipe();
        $other = ProductVariant::forceCreate(['product_id' => $this->variant->product_id, 'name' => 'L', 'price' => 40000, 'status' => 'unavailable']);
        $this->post('/admin/product-variants/'.$other->id.'/recipes', ['ingredient_id' => $this->ingredient->id, 'quantity' => '25.000'])->assertSessionHasNoErrors();
        $this->assertDatabaseCount('product_recipes', 2);
        DB::table('inventory_transactions')->insert(['ingredient_id' => $this->ingredient->id, 'type' => 'import', 'quantity' => 1000, 'before_quantity' => 0, 'after_quantity' => 1000, 'created_by' => auth('web')->id()]);
        $history = DB::table('inventory_transactions')->first();
        $this->followingRedirects()->from($this->uri())->put($this->uri($recipe), ['quantity' => '-1', 'editing_recipe' => $recipe->id])
            ->assertOk()->assertSee('value="-1"', false)->assertSee('id="quantity-error-'.$recipe->id.'"', false);
        $this->delete($this->uri($recipe))->assertRedirect();
        $this->assertEquals($history, DB::table('inventory_transactions')->first());
        $this->assertSame('1000.000', $this->ingredient->fresh()->current_stock);
        $this->assertDatabaseCount('product_recipes', 1);
    }

    public function test_malformed_input_renders_errors_and_mutations_require_csrf(): void
    {
        $this->login();
        $this->followingRedirects()->from($this->uri())->post($this->uri(), ['ingredient_id' => ['bad'], 'quantity' => ['bad']])->assertOk()->assertSee('aria-invalid="true"', false);
        $this->app->instance('env', 'local');
        $recipe = $this->recipe();
        $this->post($this->uri(), ['ingredient_id' => $this->ingredient->id, 'quantity' => 1])->assertStatus(419);
        $this->put($this->uri($recipe), ['quantity' => 1])->assertStatus(419);
        $this->delete($this->uri($recipe))->assertStatus(419);
    }
}
