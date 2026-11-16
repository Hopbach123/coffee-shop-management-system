<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductRecipe;
use App\Models\ProductVariant;
use App\Models\Ingredient;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class InternalWorkspaceFlowTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->assertSame('sqlite', DB::connection()->getDriverName());
        $this->assertSame(':memory:', DB::connection()->getDatabaseName());

        (require database_path('migrations/0001_01_01_000000_create_users_table.php'))->up();
        (require database_path('migrations/2026_08_14_000003_create_categories_table.php'))->up();
        (require database_path('migrations/2026_08_14_000007_create_products_table.php'))->up();
        (require database_path('migrations/2026_08_14_000004_create_toppings_table.php'))->up();
        (require database_path('migrations/2026_08_14_000005_create_ingredients_table.php'))->up();

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

        Schema::create('orders', function (Blueprint $table): void {
            $table->id();
            $table->string('order_code', 50)->unique();
            $table->foreignId('user_id')->nullable()->constrained('users');
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->unsignedBigInteger('table_id')->nullable();
            $table->unsignedBigInteger('reservation_id')->nullable();
            $table->enum('order_type', ['dine_in', 'takeaway'])->default('takeaway');
            $table->enum('status', ['pending', 'preparing', 'ready', 'completed', 'cancelled'])->default('pending');
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->text('note')->nullable();
            $table->dateTime('ordered_at')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('order_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id')->constrained('orders');
            $table->unsignedBigInteger('product_id')->nullable();
            $table->unsignedBigInteger('variant_id')->nullable();
            $table->string('product_name', 100);
            $table->string('variant_name', 50)->nullable();
            $table->decimal('unit_price', 12, 2);
            $table->integer('quantity');
            $table->decimal('subtotal', 15, 2);
            $table->enum('status', ['pending', 'preparing', 'ready'])->default('pending');
            $table->text('note')->nullable();
            $table->timestamps();
        });

        Schema::create('order_item_toppings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_item_id')->constrained('order_items');
            $table->unsignedBigInteger('topping_id')->nullable();
            $table->string('topping_name', 50);
            $table->decimal('price', 12, 2);
            $table->timestamps();
        });

        Schema::create('order_promotions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id')->constrained('orders');
            $table->unsignedBigInteger('promotion_id')->nullable();
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('payments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id')->constrained('orders');
            $table->string('payment_code', 50)->unique();
            $table->enum('payment_method', ['cash', 'bank_transfer']);
            $table->decimal('amount', 15, 2);
            $table->string('transaction_code', 100)->nullable();
            $table->enum('status', ['pending', 'paid', 'failed', 'refunded'])->default('pending');
            $table->dateTime('paid_at')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });

        Schema::create('product_recipes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('variant_id')->constrained('product_variants');
            $table->foreignId('ingredient_id')->constrained('ingredients');
            $table->decimal('quantity', 12, 3);
            $table->timestamps();
        });

        $this->withoutVite();
    }

    private function user(string $role): User
    {
        return User::forceCreate([
            'name' => 'Test User ' . $role,
            'email' => $role . '_' . uniqid() . '@example.test',
            'password' => 'test-password',
            'role' => $role,
            'status' => 'active',
        ]);
    }

    public function test_guest_is_redirected_from_orders_and_recipes(): void
    {
        $this->get('/staff/orders')->assertRedirect('/login');
        $this->get('/staff/brewing')->assertRedirect('/login');
    }

    public function test_staff_can_view_orders_index_with_detail_link(): void
    {
        $staff = $this->user('staff');
        $order = Order::forceCreate([
            'order_code' => 'ORD-1001',
            'user_id' => $staff->id,
            'order_type' => 'takeaway',
            'status' => 'pending',
            'subtotal' => 50000,
            'total_amount' => 50000,
            'ordered_at' => now(),
        ]);

        $response = $this->actingAs($staff, 'web')->get(route('staff.orders.index'));
        $response->assertOk();
        $response->assertSee('ORD-1001');
        $response->assertSee(route('staff.orders.show', $order));
    }

    public function test_staff_can_view_order_show_screen(): void
    {
        $staff = $this->user('staff');
        $order = Order::forceCreate([
            'order_code' => 'ORD-1002',
            'user_id' => $staff->id,
            'order_type' => 'takeaway',
            'status' => 'pending',
            'subtotal' => 65000,
            'total_amount' => 65000,
            'ordered_at' => now(),
        ]);

        $response = $this->actingAs($staff, 'web')->get(route('staff.orders.show', $order));
        $response->assertOk();
        $response->assertSee('ORD-1002');
        $response->assertSee('TỔNG KẾT TÀI CHÍNH');
        $response->assertSee('Lịch sử thanh toán thực tế');
    }

    public function test_brewing_queue_excludes_cancelled_and_completed_orders(): void
    {
        $staff = $this->user('staff');
        $cat = Category::forceCreate(['name' => 'Coffee Cat', 'status' => 'active', 'display_order' => 1]);
        $product = Product::forceCreate(['category_id' => $cat->id, 'name' => 'Espresso Test', 'slug' => 'espresso-test', 'status' => 'available']);
        $variant = ProductVariant::forceCreate(['product_id' => $product->id, 'name' => 'Size M', 'price' => 35000, 'status' => 'available']);

        // Active Order
        $activeOrder = Order::forceCreate([
            'order_code' => 'ORD-ACTIVE',
            'user_id' => $staff->id,
            'order_type' => 'takeaway',
            'status' => 'pending',
            'subtotal' => 35000,
            'total_amount' => 35000,
            'ordered_at' => now(),
        ]);

        OrderItem::forceCreate([
            'order_id' => $activeOrder->id,
            'product_id' => $product->id,
            'variant_id' => $variant->id,
            'product_name' => 'Espresso Test Active',
            'variant_name' => 'Size M',
            'unit_price' => 35000,
            'quantity' => 1,
            'subtotal' => 35000,
            'status' => 'pending',
        ]);

        // Cancelled Order
        $cancelledOrder = Order::forceCreate([
            'order_code' => 'ORD-CANCELLED',
            'user_id' => $staff->id,
            'order_type' => 'takeaway',
            'status' => 'cancelled',
            'subtotal' => 35000,
            'total_amount' => 35000,
            'ordered_at' => now(),
        ]);

        OrderItem::forceCreate([
            'order_id' => $cancelledOrder->id,
            'product_id' => $product->id,
            'variant_id' => $variant->id,
            'product_name' => 'Espresso Test Cancelled',
            'variant_name' => 'Size M',
            'unit_price' => 35000,
            'quantity' => 1,
            'subtotal' => 35000,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($staff, 'web')->get(route('staff.brewing.index'));
        $response->assertOk();
        $response->assertSee('ORD-ACTIVE');
        $response->assertDontSee('ORD-CANCELLED');
    }

    public function test_admin_can_view_variant_recipes_page(): void
    {
        $admin = $this->user('admin');
        $cat = Category::forceCreate(['name' => 'Tea Cat', 'status' => 'active', 'display_order' => 2]);
        $product = Product::forceCreate(['category_id' => $cat->id, 'name' => 'Tea Test', 'slug' => 'tea-test', 'status' => 'available']);
        $variant = ProductVariant::forceCreate(['product_id' => $product->id, 'name' => 'Size L', 'price' => 40000, 'status' => 'available']);

        $ingredient = Ingredient::forceCreate([
            'name' => 'Trà Đen',
            'unit' => 'gram',
            'minimum_stock' => 100,
            'cost_price' => 500,
            'current_stock' => 500,
        ]);

        ProductRecipe::forceCreate([
            'variant_id' => $variant->id,
            'ingredient_id' => $ingredient->id,
            'quantity' => 15.5,
        ]);

        $response = $this->actingAs($admin, 'web')->get(route('admin.product-variants.recipes', $variant));
        $response->assertOk();
        $response->assertSee('Trà Đen');
        $response->assertSee('15,500');
    }
}
