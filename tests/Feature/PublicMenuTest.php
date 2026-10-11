<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PublicMenuTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame('sqlite', DB::connection()->getDriverName());
        $this->assertSame(':memory:', DB::connection()->getDatabaseName());
        (require database_path('migrations/0001_01_01_000000_create_users_table.php'))->up();
        (require database_path('migrations/2026_08_14_000003_create_categories_table.php'))->up();
        (require database_path('migrations/2026_08_14_000007_create_products_table.php'))->up();
        // The production Variant migration contains MySQL-only CHECK DDL.
        Schema::create('product_variants', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_id')->constrained('products');
            $table->string('name', 50);
            $table->string('sku', 50)->nullable();
            $table->decimal('price', 12, 2);
            $table->string('status')->default('available');
            $table->softDeletes();
            $table->timestamps();
        });
        $this->withoutVite();
    }

    private function category(array $changes = []): Category
    {
        return Category::forceCreate(array_replace([
            'name' => 'Cà phê', 'description' => 'Các món cà phê',
            'display_order' => 0, 'status' => 'active',
        ], $changes));
    }

    private function product(Category $category, array $changes = []): Product
    {
        return Product::forceCreate(array_replace([
            'category_id' => $category->id, 'name' => 'Cà phê sữa',
            'slug' => 'ca-phe-sua', 'description' => 'Cà phê với sữa',
            'image' => null, 'status' => 'available', 'is_featured' => false,
        ], $changes));
    }

    private function variant(Product $product, array $changes = []): ProductVariant
    {
        return ProductVariant::forceCreate(array_replace([
            'product_id' => $product->id, 'name' => 'Nhỏ',
            'price' => '25000.00', 'status' => 'available',
        ], $changes));
    }

    private function user(string $role): User
    {
        return User::forceCreate([
            'name' => 'Menu visitor', 'email' => $role.'@example.test',
            'password' => 'test-password', 'role' => $role, 'status' => 'active',
        ]);
    }

    public function test_menu_routes_are_public_read_only_and_keep_internal_sessions(): void
    {
        $category = $this->category();
        $product = $this->product($category);
        $this->variant($product);

        foreach (['menu.index', 'menu.show'] as $name) {
            $route = app('router')->getRoutes()->getByName($name);
            $this->assertNotNull($route);
            $this->assertNotContains('auth:web', $route->gatherMiddleware());
            $this->assertNotContains('role:admin', $route->gatherMiddleware());
            $this->assertNotContains('role:staff', $route->gatherMiddleware());
        }

        $this->get('/menu')->assertOk()->assertSee('Cà phê sữa')
            ->assertSee(route('login'))->assertDontSee('Trang quản trị')->assertDontSee('Khu vực nhân viên');
        $this->get('/menu/ca-phe-sua')->assertOk()->assertSee('25.000,00 ₫');
        $this->post('/menu')->assertStatus(405);

        foreach (['admin', 'staff'] as $role) {
            $user = $this->user($role);
            $this->actingAs($user, 'web');
            $this->get('/menu')->assertOk()->assertSee($role === 'admin' ? 'Trang quản trị' : 'Khu vực nhân viên');
            $this->get('/menu/ca-phe-sua')->assertOk();
            $this->get(route($role.'.home'))->assertOk();
            $this->assertAuthenticatedAs($user, 'web');
        }
    }

    public function test_index_reads_real_catalog_orders_categories_and_shows_variant_prices(): void
    {
        $later = $this->category(['name' => 'Trà', 'display_order' => 2]);
        $first = $this->category(['name' => 'Cà phê', 'display_order' => 1]);
        $product = $this->product($first, ['is_featured' => true]);
        $this->variant($product, ['name' => 'Nhỏ', 'price' => '25000.00']);
        $this->variant($product, ['name' => 'Lớn', 'price' => '35000.00']);
        $this->product($later, ['name' => 'Trà đào', 'slug' => 'tra-dao', 'status' => 'unavailable']);

        $response = $this->get('/menu')->assertOk()->assertViewIs('public.menu.index')
            ->assertSee('Cà phê sữa')->assertSee('Trà đào')->assertSee('Món nổi bật')
            ->assertSee('Nhỏ')->assertSee('Lớn')
            ->assertSee('25.000,00 ₫')->assertSee('35.000,00 ₫')
            ->assertSee('Hết món')->assertSee('Chưa có giá bán')
            ->assertSee(route('menu.show', ['product' => 'ca-phe-sua']))
            ->assertDontSee('name="_method"', false);
        $this->assertTrue(strpos($response->getContent(), 'Cà phê</h2>') < strpos($response->getContent(), 'Trà</h2>'));
        $this->assertDatabaseCount('products', 2);
    }

    public function test_inactive_and_deleted_categories_and_deleted_products_are_hidden(): void
    {
        $active = $this->category();
        $inactive = $this->category(['name' => 'Ẩn', 'status' => 'inactive']);
        $deleted = $this->category(['name' => 'Đã xóa']);
        $deleted->delete();
        $visible = $this->product($active);
        $this->product($inactive, ['name' => 'Món danh mục ẩn', 'slug' => 'mon-an']);
        $this->product($deleted, ['name' => 'Món danh mục xóa', 'slug' => 'mon-xoa']);
        $removed = $this->product($active, ['name' => 'Món đã xóa', 'slug' => 'mon-da-xoa']);
        $removed->delete();

        $this->get('/menu')->assertOk()->assertSee($visible->name)
            ->assertDontSee('Món danh mục ẩn')->assertDontSee('Món danh mục xóa')
            ->assertDontSee('Món đã xóa')->assertDontSee('>Ẩn</a>', false);
        $this->get('/menu/mon-an')->assertNotFound();
        $this->get('/menu/mon-xoa')->assertNotFound();
        $this->get('/menu/mon-da-xoa')->assertNotFound();
    }

    public function test_unavailable_product_and_variant_are_visible_with_clear_status(): void
    {
        $category = $this->category();
        $product = $this->product($category, ['status' => 'unavailable']);
        $this->variant($product, ['name' => 'Nhỏ', 'price' => '25000.00', 'status' => 'unavailable']);
        $this->variant($product, ['name' => 'Lớn', 'price' => '35000.00']);
        $deleted = $this->variant($product, ['name' => 'Ẩn', 'price' => '99000.00']);
        $deleted->delete();

        $this->get('/menu')->assertOk()->assertSee('Hết món')->assertSee('Tạm hết')
            ->assertSee('25.000,00 ₫')->assertSee('35.000,00 ₫')
            ->assertDontSee('99.000,00 ₫');
        $this->get('/menu/ca-phe-sua')->assertOk()->assertSee('Hết món')->assertSee('Tạm hết')
            ->assertSee('25.000,00 ₫')->assertSee('35.000,00 ₫')
            ->assertDontSee('99.000,00 ₫');
    }

    public function test_detail_uses_slug_and_has_image_fallback_and_missing_slug_404(): void
    {
        $category = $this->category();
        $product = $this->product($category);
        $this->variant($product);

        $this->get('/menu/ca-phe-sua')->assertOk()->assertViewIs('public.menu.show')
            ->assertSee($category->name)->assertSee($product->description)
            ->assertSee('public-menu-detail__media')->assertSee('25.000,00 ₫')
            ->assertSee(route('menu.index'));
        $this->get('/menu/99999')->assertNotFound();
        $this->get('/menu/khong-ton-tai')->assertNotFound();

        $product->image = 'images/coffeehouse/artisan-espresso.png';
        $product->save();
        $this->get('/menu/ca-phe-sua')->assertOk()->assertSee(asset($product->image));
    }

    public function test_menu_uses_public_disk_image_and_falls_back_when_file_is_missing(): void
    {
        Storage::fake('public');
        $category = $this->category();
        $path = UploadedFile::fake()->image('latte.jpg')->store('products', 'public');
        $product = $this->product($category, ['image' => $path]);
        $url = Storage::disk('public')->url($path);

        $this->get('/menu')->assertOk()->assertSee($url)
            ->assertSee('data-menu-product', false);
        $this->get('/menu/ca-phe-sua')->assertOk()->assertSee($url);

        Storage::disk('public')->delete($path);
        $this->get('/menu')->assertOk()->assertDontSee($url)
            ->assertSee('<span aria-hidden="true">M</span>', false);
        $this->get('/menu/ca-phe-sua')->assertOk()->assertDontSee($url)
            ->assertSee('<span aria-hidden="true">M</span>', false);
        $this->assertSame($path, $product->fresh()->image);
    }

    public function test_empty_states_and_category_navigation_render(): void
    {
        $this->get('/menu')->assertOk()->assertSee('Thực đơn đang được cập nhật');
        $category = $this->category();
        $this->get('/menu')->assertOk()->assertSee('Chưa có món trong thực đơn')
            ->assertSee('Danh mục này chưa có món')->assertSee('href="#category-1"', false);
        $product = $this->product($category);
        $this->get('/menu/ca-phe-sua')->assertOk()->assertSee('Chưa có kích cỡ hoặc giá bán');
        $this->assertNotNull($product->fresh());
    }

    public function test_index_eager_loads_variants_across_categories(): void
    {
        foreach (['Coffee', 'Tea'] as $name) {
            $category = $this->category(['name' => $name]);
            for ($i = 1; $i <= 3; $i++) {
                $product = $this->product($category, ['name' => $name.$i, 'slug' => strtolower($name).$i]);
                $this->variant($product);
            }
        }

        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->get('/menu')->assertOk();
        $queries = array_column(DB::getQueryLog(), 'query');
        DB::disableQueryLog();
        $variantQueries = array_filter($queries, fn ($sql) => str_contains(strtolower($sql), 'from "product_variants"'));
        $this->assertCount(1, $variantQueries);
    }
}
