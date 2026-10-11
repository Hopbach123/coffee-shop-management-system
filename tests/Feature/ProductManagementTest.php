<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ProductManagementTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Product fixtures must never touch the local MySQL development database.
        $this->assertSame('sqlite', DB::connection()->getDriverName());
        $this->assertSame(':memory:', DB::connection()->getDatabaseName());

        foreach ([
            '0001_01_01_000000_create_users_table.php',
            '2026_08_14_000003_create_categories_table.php',
            '2026_08_14_000007_create_products_table.php',
        ] as $migration) {
            (require database_path('migrations/'.$migration))->up();
        }
    }

    private function user(string $role = 'admin'): User
    {
        return User::forceCreate([
            'name' => 'Product test user',
            'email' => $role.'@example.test',
            'password' => 'test-password',
            'role' => $role,
            'status' => 'active',
        ]);
    }

    private function category(array $overrides = []): Category
    {
        return Category::forceCreate(array_replace([
            'name' => 'Coffee',
            'status' => 'active',
        ], $overrides));
    }

    private function product(Category $category, array $overrides = []): Product
    {
        return Product::forceCreate(array_replace([
            'category_id' => $category->id,
            'name' => 'Latte',
            'slug' => 'latte',
            'description' => 'Milk coffee',
            'status' => 'available',
            'is_featured' => false,
        ], $overrides));
    }

    private function payload(Category $category, array $overrides = []): array
    {
        return array_replace([
            'category_id' => $category->id,
            'name' => 'Latte',
            'description' => 'Milk coffee',
            'status' => 'available',
            'is_featured' => '0',
        ], $overrides);
    }

    public function test_routes_are_admin_only_for_guests_and_staff(): void
    {
        $category = $this->category();
        $product = $this->product($category);

        foreach (['index', 'create', 'store', 'edit', 'update', 'destroy', 'toggle-status'] as $action) {
            $route = app('router')->getRoutes()->getByName('admin.products.'.$action);
            $this->assertNotNull($route);
            $this->assertContains('auth:web', $route->gatherMiddleware());
            $this->assertContains('role:admin', $route->gatherMiddleware());
        }

        $endpoints = [
            ['GET', '/admin/products'],
            ['GET', '/admin/products/create'],
            ['POST', '/admin/products'],
            ['GET', '/admin/products/'.$product->id.'/edit'],
            ['PUT', '/admin/products/'.$product->id],
            ['PATCH', '/admin/products/'.$product->id.'/toggle-status'],
            ['DELETE', '/admin/products/'.$product->id],
        ];

        foreach ($endpoints as [$method, $url]) {
            $this->call($method, $url, $this->payload($category))->assertRedirect('/login');
        }

        $this->actingAs($this->user('staff'), 'web');
        foreach ($endpoints as [$method, $url]) {
            $this->call($method, $url, $this->payload($category))->assertForbidden();
        }

        $this->assertDatabaseCount('products', 1);
    }

    public function test_admin_can_render_list_create_and_edit_with_category_relationship(): void
    {
        $this->withoutVite();
        $category = $this->category(['name' => 'Hot drinks']);
        $product = $this->product($category);
        $this->actingAs($this->user(), 'web');

        $this->get('/admin/products')->assertOk()->assertViewIs('admin.products.index')
            ->assertSee('Latte')->assertSee('Hot drinks')->assertSee('Có sẵn');
        $this->get('/admin/products/create')->assertOk()->assertViewIs('admin.products.create')
            ->assertSee('Hot drinks')->assertDontSee('name="slug"', false);
        $this->get('/admin/products/'.$product->id.'/edit')->assertOk()->assertViewIs('admin.products.edit')
            ->assertSee('value="Latte"', false)->assertSee('value="'.$category->id.'" selected', false);
    }

    public function test_create_uses_only_approved_fields_and_allows_inactive_category(): void
    {
        $category = $this->category(['status' => 'inactive']);
        $this->actingAs($this->user(), 'web')->post('/admin/products', $this->payload($category, [
            'status' => 'unavailable',
            'is_featured' => '1',
            'slug' => 'client-override',
            'image' => 'unapproved.jpg',
            'id' => 9999,
            'deleted_at' => '2000-01-01',
        ]))->assertRedirect(route('admin.products.index'))->assertSessionHas('success')->assertSessionHasNoErrors();

        $product = Product::firstOrFail();
        $this->assertSame($category->id, $product->category_id);
        $this->assertSame('latte', $product->slug);
        $this->assertSame('unavailable', $product->status);
        $this->assertTrue($product->is_featured);
        $this->assertNull($product->image);
        $this->assertNull($product->deleted_at);
        $this->assertNotSame(9999, $product->id);
    }

    public function test_validation_rejects_missing_deleted_or_invalid_categories_and_bad_fields(): void
    {
        $category = $this->category();
        $deleted = $this->category(['name' => 'Deleted']);
        $deleted->delete();
        $this->actingAs($this->user(), 'web');

        foreach ([
            ['category_id', null, 'category_id'],
            ['category_id', 999999, 'category_id'],
            ['category_id', $deleted->id, 'category_id'],
            ['name', '', 'name'],
            ['name', str_repeat('a', 256), 'name'],
            ['status', 'active', 'status'],
            ['is_featured', 'yes', 'is_featured'],
            ['description', ['invalid'], 'description'],
        ] as [$field, $value, $error]) {
            $this->from('/admin/products/create')->post('/admin/products', $this->payload($category, [$field => $value]))
                ->assertRedirect('/admin/products/create')->assertSessionHasErrors($error);
        }

        $this->from('/admin/products/create')->post('/admin/products', $this->payload($category, ['name' => '']))
            ->assertSessionHas('_old_input.description', 'Milk coffee');
        $this->assertDatabaseCount('products', 0);
    }

    public function test_slug_is_generated_with_suffixes_and_preserved_when_renaming(): void
    {
        $category = $this->category();
        $product = $this->product($category);
        $this->actingAs($this->user(), 'web');

        $this->post('/admin/products', $this->payload($category))
            ->assertRedirect(route('admin.products.index'))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('products', ['name' => 'Latte', 'slug' => 'latte-2']);

        $this->put('/admin/products/'.$product->id, $this->payload($category, ['name' => 'New Latte']))
            ->assertRedirect(route('admin.products.index'))->assertSessionHasNoErrors();
        $this->assertSame('latte', $product->fresh()->slug);

        $product->delete();
        $this->post('/admin/products', $this->payload($category))
            ->assertRedirect(route('admin.products.index'))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('products', ['name' => 'Latte', 'slug' => 'latte-3']);

        $this->post('/admin/products', $this->payload($category, ['name' => 'Cà phê sữa', 'slug' => 'ignored']))
            ->assertRedirect(route('admin.products.index'))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('products', ['name' => 'Cà phê sữa', 'slug' => 'ca-phe-sua']);

        $this->post('/admin/products', $this->payload($category, ['name' => '☕']))
            ->assertRedirect(route('admin.products.index'))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('products', ['name' => '☕', 'slug' => 'product']);

        $longName = str_repeat('a', 255);
        $this->post('/admin/products', $this->payload($category, ['name' => $longName]))
            ->assertRedirect(route('admin.products.index'))->assertSessionHasNoErrors();
        $this->post('/admin/products', $this->payload($category, ['name' => $longName]))
            ->assertRedirect(route('admin.products.index'))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('products', ['slug' => str_repeat('a', 253).'-2']);
    }

    public function test_update_changes_approved_fields_and_preserves_image_and_system_fields(): void
    {
        $category = $this->category();
        $otherCategory = $this->category(['name' => 'Tea']);
        $product = $this->product($category, ['image' => 'products/existing.jpg']);
        $createdAt = $product->created_at;

        $this->actingAs($this->user(), 'web')->put('/admin/products/'.$product->id, $this->payload($otherCategory, [
            'name' => 'Matcha',
            'slug' => 'unapproved-slug',
            'description' => null,
            'status' => 'unavailable',
            'is_featured' => '1',
            'image' => 'unapproved.jpg',
            'id' => 9999,
            'created_at' => '2000-01-01',
            'deleted_at' => '2000-01-01',
        ]))->assertRedirect(route('admin.products.index'))->assertSessionHas('success')->assertSessionHasNoErrors();

        $product->refresh();
        $this->assertSame($otherCategory->id, $product->category_id);
        $this->assertSame('Matcha', $product->name);
        $this->assertSame('latte', $product->slug);
        $this->assertNull($product->description);
        $this->assertSame('unavailable', $product->status);
        $this->assertTrue($product->is_featured);
        $this->assertSame('products/existing.jpg', $product->image);
        $this->assertTrue($createdAt->equalTo($product->created_at));
        $this->assertNull($product->deleted_at);
    }

    public function test_product_with_deleted_category_remains_visible_but_must_be_reassigned_on_edit(): void
    {
        $this->withoutVite();
        $category = $this->category(['name' => 'Old category']);
        $product = $this->product($category);
        $category->delete();
        $replacement = $this->category(['name' => 'New category']);
        $this->actingAs($this->user(), 'web');

        $this->get('/admin/products')->assertOk()->assertSee('Old category')->assertSee('(đã xóa)');
        $this->get('/admin/products/'.$product->id.'/edit')->assertOk()
            ->assertSee('Danh mục hiện tại đã bị xóa')
            ->assertViewHas('categories', fn ($categories) => ! $categories->contains('id', $category->id))
            ->assertSee('value="'.$replacement->id.'"', false);

        $this->put('/admin/products/'.$product->id, $this->payload($replacement))
            ->assertRedirect(route('admin.products.index'))->assertSessionHasNoErrors();
        $this->assertSame($replacement->id, $product->fresh()->category_id);
    }

    public function test_admin_can_toggle_both_statuses_and_soft_delete_without_losing_row(): void
    {
        $category = $this->category();
        $product = $this->product($category);
        $this->actingAs($this->user(), 'web');

        foreach (['unavailable', 'available'] as $expected) {
            $this->patch('/admin/products/'.$product->id.'/toggle-status')
                ->assertRedirect(route('admin.products.index'))->assertSessionHas('success');
            $this->assertSame($expected, $product->fresh()->status);
        }

        $this->delete('/admin/products/'.$product->id)->assertRedirect(route('admin.products.index'));
        $this->assertSoftDeleted('products', ['id' => $product->id]);
        $this->assertNull(Product::find($product->id));
        $this->assertNotNull(Product::withTrashed()->find($product->id));
        $this->get('/admin/products/'.$product->id.'/edit')->assertNotFound();
    }

    public function test_search_pagination_and_empty_states_preserve_query_and_exclude_deleted(): void
    {
        $this->withoutVite();
        $category = $this->category();
        $this->actingAs($this->user(), 'web');

        $this->get('/admin/products')->assertOk()->assertSee('Chưa có sản phẩm');
        for ($index = 1; $index <= 16; $index++) {
            $this->product($category, ['name' => 'Latte '.$index, 'slug' => 'latte-'.$index]);
        }
        $this->product($category, ['name' => 'Tea', 'slug' => 'tea', 'description' => 'Latte in description']);
        $this->product($category, ['name' => 'Latte deleted', 'slug' => 'latte-deleted'])->delete();

        $this->get('/admin/products?search=Missing')->assertOk()->assertSee('Không tìm thấy sản phẩm');
        $this->get('/admin/products?search=Latte')->assertOk()
            ->assertSee('search=Latte&amp;page=2', false)->assertDontSee('Latte deleted');
        $this->get('/admin/products?search=Latte&page=2')->assertOk()
            ->assertSee('Latte 16')->assertDontSee('Tea');
    }

    public function test_list_eager_loads_categories_in_one_query(): void
    {
        $this->withoutVite();
        $category = $this->category();
        for ($index = 1; $index <= 5; $index++) {
            $this->product($category, ['name' => 'Latte '.$index, 'slug' => 'latte-'.$index]);
        }
        $this->actingAs($this->user(), 'web');
        DB::connection()->enableQueryLog();

        $this->get('/admin/products')->assertOk();

        $categoryQueries = array_filter(DB::connection()->getQueryLog(), fn ($query) => str_contains(strtolower($query['query']), 'select') &&
            str_contains(strtolower($query['query']), 'categories')
        );
        $this->assertCount(1, $categoryQueries);
    }
}
