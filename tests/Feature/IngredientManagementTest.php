<?php

namespace Tests\Feature;

use App\Models\Ingredient;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class IngredientManagementTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame('sqlite', DB::connection()->getDriverName());
        $this->assertSame(':memory:', DB::connection()->getDatabaseName());
        foreach (['0001_01_01_000000_create_users_table.php', '2026_08_14_000005_create_ingredients_table.php'] as $migration) {
            (require database_path('migrations/'.$migration))->up();
        }
        // Use real schema definitions; MySQL ALTER CHECK syntax is not supported by SQLite.
        foreach (['2026_08_14_000003_create_categories_table.php', '2026_08_14_000007_create_products_table.php', '2026_08_16_000014_create_product_variants_table.php', '2026_08_16_000021_create_product_recipes_table.php', '2026_08_16_000022_create_inventory_transactions_table.php'] as $migration) {
            $queries = DB::pretend(fn () => (require database_path('migrations/'.$migration))->up());
            foreach ($queries as $query) {
                if (! str_starts_with(strtoupper(ltrim($query['query'])), 'ALTER TABLE')) {
                    DB::statement($query['query'], $query['bindings']);
                }
            }
        }
    }

    private function user(string $role = 'admin'): User
    {
        return User::forceCreate(['name' => 'Ingredient tester', 'email' => $role.'@example.test', 'password' => 'test-password', 'role' => $role, 'status' => 'active']);
    }

    private function payload(array $overrides = []): array
    {
        return array_replace(['name' => 'Cà phê', 'unit' => 'kg', 'minimum_stock' => '1.250', 'cost_price' => '150000.50', 'status' => 'active'], $overrides);
    }

    private function ingredient(array $overrides = []): Ingredient
    {
        return Ingredient::forceCreate($this->payload($overrides));
    }

    public function test_guest_and_staff_cannot_read_or_mutate_ingredients(): void
    {
        $ingredient = $this->ingredient();
        $endpoints = [['GET', '/admin/ingredients'], ['GET', '/admin/ingredients/create'], ['POST', '/admin/ingredients'], ['GET', '/admin/ingredients/'.$ingredient->id.'/edit'], ['PUT', '/admin/ingredients/'.$ingredient->id], ['PATCH', '/admin/ingredients/'.$ingredient->id], ['PATCH', '/admin/ingredients/'.$ingredient->id.'/toggle-status'], ['DELETE', '/admin/ingredients/'.$ingredient->id]];
        foreach ($endpoints as [$method, $uri]) {
            $this->call($method, $uri, $this->payload())->assertRedirect('/login');
        }
        $this->actingAs($this->user('staff'), 'web');
        foreach ($endpoints as [$method, $uri]) {
            $this->call($method, $uri, $this->payload())->assertForbidden();
        }
        $this->assertDatabaseCount('ingredients', 1);
        $this->assertDatabaseHas('ingredients', ['id' => $ingredient->id, 'status' => 'active', 'deleted_at' => null]);
        $this->withoutVite()->get('/staff')->assertOk()->assertDontSee(route('admin.ingredients.index'));
    }

    public function test_admin_pages_render_and_do_not_offer_stock_editing(): void
    {
        $this->withoutVite();
        $ingredient = $this->ingredient(['current_stock' => '5.125']);
        $this->actingAs($this->user(), 'web');
        $this->get('/admin/ingredients')->assertOk()->assertSee('Cà phê')->assertSee('5.125')->assertSee('kg');
        $this->get('/admin/ingredients/create')->assertOk()->assertSee('name="unit"', false)->assertDontSee('name="current_stock"', false);
        $this->get('/admin/ingredients/'.$ingredient->id.'/edit')->assertOk()->assertSee('5.125')->assertSee('value="Cà phê"', false)->assertDontSee('name="current_stock"', false);
    }

    public function test_create_and_update_whitelist_metadata_and_preserve_stock(): void
    {
        $this->actingAs($this->user(), 'web');
        $this->post('/admin/ingredients', $this->payload(['current_stock' => '999', 'id' => 9999, 'deleted_at' => '2000-01-01']))
            ->assertRedirect(route('admin.ingredients.index'))->assertSessionHasNoErrors()->assertSessionHas('success');
        $ingredient = Ingredient::firstOrFail();
        $this->assertSame('0.000', $ingredient->current_stock);
        $this->assertNull($ingredient->deleted_at);
        $this->assertNotSame(9999, $ingredient->id);
        $ingredient->forceFill(['current_stock' => '5.125'])->save();
        $createdAt = $ingredient->created_at;
        $this->put('/admin/ingredients/'.$ingredient->id, $this->payload(['name' => 'Sữa', 'status' => 'inactive', 'minimum_stock' => '0', 'cost_price' => '0', 'current_stock' => '-1', 'created_at' => '2000-01-01', 'deleted_at' => '2000-01-01']))
            ->assertRedirect(route('admin.ingredients.index'))->assertSessionHasNoErrors();
        $ingredient->refresh();
        $this->assertSame('Sữa', $ingredient->name);
        $this->assertSame('inactive', $ingredient->status);
        $this->assertSame('5.125', $ingredient->current_stock);
        $this->assertSame('0.000', $ingredient->minimum_stock);
        $this->assertSame('0.00', $ingredient->cost_price);
        $this->assertTrue($createdAt->equalTo($ingredient->created_at));
        $this->assertNull($ingredient->deleted_at);
    }

    public function test_create_and_update_validate_schema_limits(): void
    {
        $ingredient = $this->ingredient();
        $this->actingAs($this->user(), 'web');
        foreach ([['name', ''], ['name', str_repeat('a', 256)], ['name', ['bad']], ['unit', ''], ['unit', str_repeat('a', 51)], ['unit', ['bad']], ['minimum_stock', '-0.001'], ['minimum_stock', '1.0001'], ['minimum_stock', '1000000000'], ['minimum_stock', null], ['cost_price', '-0.01'], ['cost_price', '1.001'], ['cost_price', '10000000000'], ['cost_price', 'bad'], ['status', 'available'], ['status', null]] as [$field, $value]) {
            $payload = $this->payload([$field => $value]);
            $this->post('/admin/ingredients', $payload)->assertSessionHasErrors($field);
            $this->put('/admin/ingredients/'.$ingredient->id, $payload)->assertSessionHasErrors($field);
        }
        $this->assertDatabaseCount('ingredients', 1);
        $this->assertSame('Cà phê', $ingredient->fresh()->name);
        $this->post('/admin/ingredients', $this->payload(['name' => str_repeat('a', 255), 'minimum_stock' => '999999999.999', 'cost_price' => '9999999999.99']))->assertSessionHasNoErrors();
    }

    public function test_validation_renders_old_input_and_accessible_errors(): void
    {
        $this->withoutVite();
        $this->actingAs($this->user(), 'web');
        $this->followingRedirects()->from('/admin/ingredients/create')->post('/admin/ingredients', $this->payload(['name' => '', 'minimum_stock' => '-1']))
            ->assertOk()->assertSee('aria-invalid="true"', false)->assertSee('id="name-error"', false)->assertSee('value="-1"', false)->assertSee('value="kg"', false);
    }

    public function test_units_are_selected_from_canonical_choices_and_reject_arbitrary_values(): void
    {
        $this->actingAs($this->user(), 'web');
        foreach (array_keys(config('inventory.units')) as $unit) {
            $this->post('/admin/ingredients', $this->payload(['unit' => $unit]))->assertSessionHasNoErrors();
        }
        foreach (['liters', 'KG', '123', '<script>', 'bao'] as $unit) {
            $this->post('/admin/ingredients', $this->payload(['unit' => $unit]))->assertSessionHasErrors('unit');
        }
        $this->withoutVite()->get('/admin/ingredients/create')->assertOk()->assertSee('<select id="unit"', false)->assertDontSee('<input id="unit"', false);
        $this->assertDatabaseCount('ingredients', 5);
    }

    public function test_malformed_array_inputs_render_validation_errors_without_server_error(): void
    {
        $this->withoutVite()->actingAs($this->user(), 'web');
        $this->followingRedirects()->from('/admin/ingredients/create')->post('/admin/ingredients', $this->payload([
            'name' => ['bad'], 'unit' => ['bad'], 'minimum_stock' => ['bad'], 'cost_price' => ['bad'], 'status' => ['bad'],
        ]))->assertOk()->assertSee('id="name-error"', false)->assertSee('id="unit-error"', false)->assertSee('id="cost_price-error"', false);
        $this->assertDatabaseCount('ingredients', 0);
    }

    public function test_other_units_are_normalized_persisted_and_protected_on_update(): void
    {
        $this->withoutVite()->actingAs($this->user(), 'web');
        $this->post('/admin/ingredients', $this->payload(['unit' => 'other', 'custom_unit' => '  HỘP   GIẤY  ']))->assertSessionHasNoErrors();
        $ingredient = Ingredient::firstOrFail();
        $this->assertSame('hộp giấy', $ingredient->unit);
        $this->get('/admin/ingredients/'.$ingredient->id.'/edit')->assertOk()->assertSee('value="hộp giấy"', false);
        $ingredient->forceFill(['current_stock' => '1'])->save();
        $this->put('/admin/ingredients/'.$ingredient->id, $this->payload(['unit' => 'other', 'custom_unit' => 'Hộp giấy']))->assertSessionHasNoErrors();
        $this->put('/admin/ingredients/'.$ingredient->id, $this->payload(['unit' => 'other', 'custom_unit' => 'chai']))->assertSessionHasErrors('unit');
        $this->assertSame('hộp giấy', $ingredient->fresh()->unit);
    }

    public function test_other_units_reject_missing_malformed_and_excessive_input(): void
    {
        $this->withoutVite()->actingAs($this->user(), 'web');
        foreach ([null, '', '123', 'other', '<script>', ['bad'], str_repeat('a', 51)] as $value) {
            $this->followingRedirects()->from('/admin/ingredients/create')->post('/admin/ingredients', $this->payload(['unit' => 'other', 'custom_unit' => $value]))->assertOk()->assertSee('id="unit-error"', false);
        }
        $this->assertDatabaseCount('ingredients', 0);
        $this->post('/admin/ingredients', $this->payload(['unit' => 'kg', 'custom_unit' => '<script>']))->assertSessionHasNoErrors();
        $this->assertSame('kg', Ingredient::firstOrFail()->unit);
    }

    public function test_unit_change_is_allowed_only_for_unused_zero_stock_ingredients(): void
    {
        $admin = $this->user();
        $this->actingAs($admin, 'web');
        $ingredient = $this->ingredient();
        $this->put('/admin/ingredients/'.$ingredient->id, $this->payload(['unit' => 'g']))->assertSessionHasNoErrors();
        $this->assertSame('g', $ingredient->fresh()->unit);
        $ingredient->forceFill(['current_stock' => '0.001'])->save();
        $this->put('/admin/ingredients/'.$ingredient->id, $this->payload())->assertSessionHasErrors('unit');
        $this->assertSame('g', $ingredient->fresh()->unit);
        $ingredient->forceFill(['current_stock' => '0'])->save();
        DB::table('inventory_transactions')->insert(['ingredient_id' => $ingredient->id, 'type' => 'waste', 'quantity' => 1, 'before_quantity' => 1, 'after_quantity' => 0, 'created_by' => $admin->id]);
        $this->put('/admin/ingredients/'.$ingredient->id, $this->payload())->assertSessionHasErrors('unit');
        $this->put('/admin/ingredients/'.$ingredient->id, $this->payload(['unit' => 'g', 'name' => 'Tên mới']))->assertSessionHasNoErrors();
        $this->assertSame('Tên mới', $ingredient->fresh()->name);
        $this->assertDatabaseCount('inventory_transactions', 1);
    }

    public function test_recipe_blocks_unit_change_even_without_stock_or_ledger(): void
    {
        $this->actingAs($this->user(), 'web');
        $ingredient = $this->ingredient();
        $categoryId = DB::table('categories')->insertGetId(['name' => 'Coffee']);
        $productId = DB::table('products')->insertGetId(['category_id' => $categoryId, 'name' => 'Coffee', 'slug' => 'coffee']);
        $variantId = DB::table('product_variants')->insertGetId(['product_id' => $productId, 'name' => 'M', 'price' => 10000]);
        DB::table('product_recipes')->insert(['variant_id' => $variantId, 'ingredient_id' => $ingredient->id, 'quantity' => '1.000']);
        $this->put('/admin/ingredients/'.$ingredient->id, $this->payload(['unit' => 'g']))->assertSessionHasErrors('unit');
        $this->assertSame('kg', $ingredient->fresh()->unit);
        $this->assertDatabaseHas('product_recipes', ['ingredient_id' => $ingredient->id, 'quantity' => 1]);
    }

    public function test_status_and_soft_delete_preserve_stock_and_hide_deleted_records(): void
    {
        $ingredient = $this->ingredient(['current_stock' => '5.125']);
        $this->actingAs($this->user(), 'web');
        foreach (['inactive', 'active'] as $status) {
            $this->patch('/admin/ingredients/'.$ingredient->id.'/toggle-status')->assertRedirect(route('admin.ingredients.index'));
            $this->assertSame($status, $ingredient->fresh()->status);
            $this->assertSame('5.125', $ingredient->fresh()->current_stock);
        }
        $this->delete('/admin/ingredients/'.$ingredient->id)->assertRedirect(route('admin.ingredients.index'));
        $this->assertSoftDeleted('ingredients', ['id' => $ingredient->id]);
        $this->assertSame('5.125', Ingredient::withTrashed()->findOrFail($ingredient->id)->current_stock);
        foreach ([$ingredient->id, 999999] as $id) {
            $this->get('/admin/ingredients/'.$id.'/edit')->assertNotFound();
            $this->put('/admin/ingredients/'.$id, $this->payload())->assertNotFound();
            $this->patch('/admin/ingredients/'.$id.'/toggle-status')->assertNotFound();
            $this->delete('/admin/ingredients/'.$id)->assertNotFound();
        }
    }

    public function test_name_search_pagination_and_empty_states(): void
    {
        $this->withoutVite();
        $this->actingAs($this->user(), 'web');
        $this->get('/admin/ingredients')->assertOk()->assertSee('Chưa có nguyên liệu');
        for ($index = 1; $index <= 16; $index++) {
            $this->ingredient(['name' => 'Coffee '.$index]);
        }
        $this->ingredient(['name' => 'Milk', 'unit' => 'Coffee']);
        $this->ingredient(['name' => 'Coffee deleted'])->delete();
        $this->get('/admin/ingredients?search=Coffee')->assertOk()->assertSee('search=Coffee&amp;page=2', false)->assertDontSee('Coffee deleted');
        $this->get('/admin/ingredients?search=Coffee&page=2')->assertOk()->assertSee('Coffee 16')->assertDontSee('Milk');
        $this->get('/admin/ingredients?search=Missing')->assertOk()->assertSee('Không tìm thấy nguyên liệu');
        $this->get('/admin/ingredients?search[]=bad')->assertSessionHasErrors('search');
    }

    public function test_mutations_require_csrf_and_status_does_not_accept_get(): void
    {
        $ingredient = $this->ingredient();
        $this->actingAs($this->user(), 'web');
        $this->app->instance('env', 'local');
        $this->post('/admin/ingredients', $this->payload())->assertStatus(419);
        $this->put('/admin/ingredients/'.$ingredient->id, $this->payload())->assertStatus(419);
        $this->patch('/admin/ingredients/'.$ingredient->id.'/toggle-status')->assertStatus(419);
        $this->delete('/admin/ingredients/'.$ingredient->id)->assertStatus(419);
        $this->get('/admin/ingredients/'.$ingredient->id.'/toggle-status')->assertStatus(405);
    }
}
