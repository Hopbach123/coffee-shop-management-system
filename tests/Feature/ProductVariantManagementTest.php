<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ProductVariantManagementTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->assertSame('sqlite', DB::connection()->getDriverName());
        $this->assertSame(':memory:', DB::connection()->getDatabaseName());

        foreach ([
            '0001_01_01_000000_create_users_table.php',
            '2026_08_14_000003_create_categories_table.php',
            '2026_08_14_000007_create_products_table.php',
        ] as $migration) {
            (require database_path('migrations/'.$migration))->up();
        }

        // The production migration adds a MySQL CHECK with raw ALTER TABLE, unsupported by SQLite.
        // Keep its approved columns and unique indexes in this isolated in-memory fixture.
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->string('name', 50);
            $table->string('sku', 50)->nullable();
            $table->decimal('price', 12, 2);
            $table->enum('status', ['available', 'unavailable'])->default('available');
            $table->softDeletes();
            $table->timestamps();
            $table->unique('sku');
            $table->unique(['product_id', 'name']);
        });
    }

    private function user(string $role = 'admin'): User
    {
        return User::forceCreate([
            'name' => 'Variant test user',
            'email' => $role.'@example.test',
            'password' => 'test-password',
            'role' => $role,
            'status' => 'active',
        ]);
    }

    private function product(string $name = 'Latte', string $status = 'available'): Product
    {
        $category = Category::forceCreate(['name' => 'Coffee', 'status' => 'active']);

        return Product::forceCreate([
            'category_id' => $category->id,
            'name' => $name,
            'slug' => strtolower($name),
            'status' => $status,
            'is_featured' => false,
        ]);
    }

    private function variant(Product $product, array $overrides = []): ProductVariant
    {
        return ProductVariant::forceCreate(array_replace([
            'product_id' => $product->id,
            'name' => 'M',
            'sku' => 'LATTE-M',
            'price' => '25000.00',
            'status' => 'available',
        ], $overrides));
    }

    private function payload(Product $product, array $overrides = []): array
    {
        return array_replace([
            'product_id' => $product->id,
            'name' => 'M',
            'sku' => 'LATTE-M',
            'price' => '25000.00',
            'status' => 'available',
        ], $overrides);
    }

    public function test_all_variant_routes_are_admin_only(): void
    {
        $product = $this->product();
        $variant = $this->variant($product);

        foreach (['index', 'create', 'store', 'edit', 'update', 'destroy', 'toggle-status'] as $action) {
            $route = app('router')->getRoutes()->getByName('admin.product-variants.'.$action);
            $this->assertNotNull($route);
            $this->assertContains('auth:web', $route->gatherMiddleware());
            $this->assertContains('role:admin', $route->gatherMiddleware());
        }

        $endpoints = [
            ['GET', '/admin/product-variants'],
            ['GET', '/admin/product-variants/create'],
            ['POST', '/admin/product-variants'],
            ['GET', '/admin/product-variants/'.$variant->id.'/edit'],
            ['PUT', '/admin/product-variants/'.$variant->id],
            ['PATCH', '/admin/product-variants/'.$variant->id.'/toggle-status'],
            ['DELETE', '/admin/product-variants/'.$variant->id],
        ];

        foreach ($endpoints as [$method, $url]) {
            $this->call($method, $url, $this->payload($product))->assertRedirect('/login');
        }

        $this->actingAs($this->user('staff'), 'web');
        foreach ($endpoints as [$method, $url]) {
            $this->call($method, $url, $this->payload($product))->assertForbidden();
        }

        $this->assertDatabaseCount('product_variants', 1);
    }

    public function test_admin_can_render_list_create_and_edit_with_product_and_price(): void
    {
        $this->withoutVite();
        $product = $this->product('Latte', 'unavailable');
        $variant = $this->variant($product);
        $this->actingAs($this->user(), 'web');

        $this->get('/admin/product-variants')->assertOk()->assertViewIs('admin.product-variants.index')
            ->assertSee('Latte')->assertSee('LATTE-M')->assertSee('25.000,00 ₫');
        $this->get('/admin/product-variants/create')->assertOk()->assertViewIs('admin.product-variants.create')
            ->assertSee('Latte')->assertSee('SKU (tùy chọn)');
        $this->get('/admin/product-variants/'.$variant->id.'/edit')->assertOk()
            ->assertViewIs('admin.product-variants.edit')
            ->assertSee('value="M"', false)
            ->assertSee('value="'.$product->id.'" selected', false);
    }

    public function test_create_accepts_optional_sku_and_only_approved_fields(): void
    {
        $product = $this->product();
        $this->actingAs($this->user(), 'web')->post('/admin/product-variants', $this->payload($product, [
            'sku' => '   ',
            'price' => '0',
            'id' => 9999,
            'deleted_at' => '2000-01-01',
            'created_at' => '2000-01-01',
        ]))->assertRedirect(route('admin.product-variants.index'))->assertSessionHas('success')->assertSessionHasNoErrors();

        $variant = ProductVariant::firstOrFail();
        $this->assertSame($product->id, $variant->product_id);
        $this->assertSame('M', $variant->name);
        $this->assertNull($variant->sku);
        $this->assertSame('0.00', $variant->price);
        $this->assertSame('available', $variant->status);
        $this->assertNull($variant->deleted_at);
        $this->assertNotSame(9999, $variant->id);
        $this->assertSame('Latte', $product->fresh()->name);
    }

    public function test_validation_rejects_missing_deleted_or_invalid_products_and_fields(): void
    {
        $product = $this->product();
        $deleted = $this->product('Deleted');
        $deleted->delete();
        $this->actingAs($this->user(), 'web');

        foreach ([
            ['product_id', null, 'product_id'],
            ['product_id', 999999, 'product_id'],
            ['product_id', $deleted->id, 'product_id'],
            ['name', '', 'name'],
            ['name', str_repeat('a', 51), 'name'],
            ['sku', str_repeat('a', 51), 'sku'],
            ['price', null, 'price'],
            ['price', '-0.01', 'price'],
            ['price', '1.234', 'price'],
            ['price', '10000000000', 'price'],
            ['status', 'active', 'status'],
        ] as [$field, $value, $error]) {
            $this->from('/admin/product-variants/create')
                ->post('/admin/product-variants', $this->payload($product, [$field => $value]))
                ->assertRedirect('/admin/product-variants/create')->assertSessionHasErrors($error);
        }

        $this->assertDatabaseCount('product_variants', 0);
    }

    public function test_form_shows_validation_errors_and_prefers_old_input(): void
    {
        $this->withoutVite();
        $product = $this->product();
        $this->actingAs($this->user(), 'web');
        $invalid = $this->payload($product, [
            'name' => '',
            'sku' => 'NEW-SKU',
            'price' => '-1',
        ]);

        $this->from('/admin/product-variants/create')->post('/admin/product-variants', $invalid)
            ->assertRedirect('/admin/product-variants/create')
            ->assertSessionHasErrors(['name', 'price'])
            ->assertSessionHas('_old_input.sku', 'NEW-SKU');

        $this->followingRedirects()->from('/admin/product-variants/create')
            ->post('/admin/product-variants', $invalid)->assertOk()
            ->assertSee('Vui lòng kiểm tra các trường')
            ->assertSee('value="NEW-SKU"', false)
            ->assertSee('value="-1"', false);
    }

    public function test_name_is_unique_within_product_including_soft_deleted_rows(): void
    {
        $product = $this->product();
        $other = $this->product('Tea');
        $variant = $this->variant($product);
        $this->actingAs($this->user(), 'web');

        $this->post('/admin/product-variants', $this->payload($product, ['sku' => null]))
            ->assertSessionHasErrors('name');
        $this->post('/admin/product-variants', $this->payload($other, ['sku' => null]))
            ->assertSessionHasNoErrors();
        $this->assertDatabaseHas('product_variants', ['product_id' => $other->id, 'name' => 'M']);

        $variant->delete();
        $this->post('/admin/product-variants', $this->payload($product, ['sku' => null]))
            ->assertSessionHasErrors('name');
    }

    public function test_sku_is_globally_unique_including_soft_deleted_rows(): void
    {
        $product = $this->product();
        $other = $this->product('Tea');
        $variant = $this->variant($product);
        $this->actingAs($this->user(), 'web');

        $this->post('/admin/product-variants', $this->payload($other))
            ->assertSessionHasErrors('sku');
        $variant->delete();
        $this->post('/admin/product-variants', $this->payload($other))
            ->assertSessionHasErrors('sku');

        $this->post('/admin/product-variants', $this->payload($other, ['sku' => null]))
            ->assertSessionHasNoErrors();
        $this->assertDatabaseHas('product_variants', ['product_id' => $other->id, 'sku' => null]);
    }

    public function test_update_ignores_self_for_unique_checks_and_rejects_conflicts_when_reparenting(): void
    {
        $product = $this->product();
        $other = $this->product('Tea');
        $variant = $this->variant($product);
        $this->variant($other, ['sku' => 'TEA-M']);
        $this->actingAs($this->user(), 'web');

        $this->put('/admin/product-variants/'.$variant->id, $this->payload($product))
            ->assertSessionHasNoErrors();
        $this->put('/admin/product-variants/'.$variant->id, $this->payload($other))
            ->assertSessionHasErrors('name');
        $this->put('/admin/product-variants/'.$variant->id, $this->payload($product, ['sku' => 'TEA-M']))
            ->assertSessionHasErrors('sku');

        $createdAt = $variant->fresh()->created_at;
        $this->put('/admin/product-variants/'.$variant->id, $this->payload($other, [
            'name' => 'L',
            'sku' => 'TEA-L',
            'price' => '29000.50',
            'status' => 'unavailable',
            'id' => 9999,
            'created_at' => '2000-01-01',
            'deleted_at' => '2000-01-01',
        ]))->assertRedirect(route('admin.product-variants.index'))->assertSessionHasNoErrors();

        $variant->refresh();
        $this->assertSame($other->id, $variant->product_id);
        $this->assertSame('L', $variant->name);
        $this->assertSame('TEA-L', $variant->sku);
        $this->assertSame('29000.50', $variant->price);
        $this->assertSame('unavailable', $variant->status);
        $this->assertTrue($createdAt->equalTo($variant->created_at));
        $this->assertNull($variant->deleted_at);
        $this->assertSame('Tea', $other->fresh()->name);
    }

    public function test_deleted_parent_remains_labeled_in_list_but_must_be_reassigned_on_edit(): void
    {
        $this->withoutVite();
        $product = $this->product('Old product');
        $variant = $this->variant($product);
        $product->delete();
        $replacement = $this->product('New product');
        $this->actingAs($this->user(), 'web');

        $this->get('/admin/product-variants')->assertOk()->assertSee('Old product')->assertSee('(đã xóa)');
        $this->get('/admin/product-variants/'.$variant->id.'/edit')->assertOk()
            ->assertSee('Sản phẩm hiện tại đã bị xóa')
            ->assertViewHas('products', fn ($products) => ! $products->contains('id', $product->id));

        $this->put('/admin/product-variants/'.$variant->id, $this->payload($replacement))
            ->assertSessionHasNoErrors();
        $this->assertSame($replacement->id, $variant->fresh()->product_id);
    }

    public function test_status_toggle_and_soft_delete_preserve_product_and_row(): void
    {
        $product = $this->product();
        $variant = $this->variant($product);
        $this->actingAs($this->user(), 'web');

        foreach (['unavailable', 'available'] as $expected) {
            $this->patch('/admin/product-variants/'.$variant->id.'/toggle-status')
                ->assertRedirect(route('admin.product-variants.index'))->assertSessionHas('success');
            $this->assertSame($expected, $variant->fresh()->status);
        }

        $this->delete('/admin/product-variants/'.$variant->id)
            ->assertRedirect(route('admin.product-variants.index'));
        $this->assertSoftDeleted('product_variants', ['id' => $variant->id]);
        $this->assertNull(ProductVariant::find($variant->id));
        $this->assertNotNull(ProductVariant::withTrashed()->find($variant->id));
        $this->assertNotNull($product->fresh());
        $this->get('/admin/product-variants/'.$variant->id.'/edit')->assertNotFound();
    }

    public function test_search_pagination_and_empty_states_exclude_deleted_and_keep_query(): void
    {
        $this->withoutVite();
        $product = $this->product();
        $this->actingAs($this->user(), 'web');

        $this->get('/admin/product-variants')->assertOk()->assertSee('Chưa có biến thể');
        for ($index = 1; $index <= 16; $index++) {
            $this->variant($product, ['name' => 'Size '.$index, 'sku' => 'SIZE-'.$index]);
        }
        $this->variant($product, ['name' => 'Special', 'sku' => 'SEARCH-SKU']);
        $this->variant($product, ['name' => 'Deleted', 'sku' => 'SIZE-DELETED'])->delete();

        $this->get('/admin/product-variants?search=Missing')->assertOk()->assertSee('Không tìm thấy biến thể');
        $this->get('/admin/product-variants?search=Size')->assertOk()
            ->assertSee('search=Size&amp;page=2', false)->assertDontSee('SIZE-DELETED');
        $this->get('/admin/product-variants?search=Size&page=2')->assertOk()
            ->assertSee('Size 16')->assertDontSee('Special');
        $this->get('/admin/product-variants?search=SEARCH-SKU')->assertOk()
            ->assertSee('Special')->assertDontSee('Size 1');
    }

    public function test_list_eager_loads_products_in_one_query(): void
    {
        $this->withoutVite();
        for ($index = 1; $index <= 5; $index++) {
            $this->variant($this->product('Coffee '.$index), ['sku' => 'SKU-'.$index]);
        }
        $this->actingAs($this->user(), 'web');
        DB::connection()->enableQueryLog();

        $this->get('/admin/product-variants')->assertOk();

        $productQueries = array_filter(DB::connection()->getQueryLog(), fn ($query) => str_contains(strtolower($query['query']), 'select') &&
            str_contains(strtolower($query['query']), 'products')
        );
        $this->assertCount(1, $productQueries);
    }
}
