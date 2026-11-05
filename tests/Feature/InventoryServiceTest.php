<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Ingredient;
use App\Models\InventoryTransaction;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductRecipe;
use App\Models\ProductVariant;
use App\Models\User;
use App\Services\InventoryService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class InventoryServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->assertSame('sqlite', config('database.default'));
        (require database_path('migrations/0001_01_01_000000_create_users_table.php'))->up();
        (require database_path('migrations/2026_08_14_000003_create_categories_table.php'))->up();
        (require database_path('migrations/2026_08_14_000005_create_ingredients_table.php'))->up();
        (require database_path('migrations/2026_08_14_000007_create_products_table.php'))->up();

        Schema::create('product_variants', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->string('name');
            $table->decimal('price', 12, 2);
            $table->string('status')->default('available');
            $table->softDeletes();
            $table->timestamps();
        });
        Schema::create('orders', function (Blueprint $table): void {
            $table->id();
            $table->string('order_code')->unique();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->string('order_type')->default('takeaway');
            $table->string('status')->default('pending');
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->dateTime('ordered_at');
            $table->timestamps();
        });
        Schema::create('order_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->restrictOnDelete();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->foreignId('variant_id')->constrained('product_variants')->restrictOnDelete();
            $table->string('product_name');
            $table->string('variant_name')->nullable();
            $table->integer('quantity');
            $table->decimal('unit_price', 12, 2);
            $table->decimal('topping_amount', 12, 2)->default(0);
            $table->decimal('subtotal', 15, 2);
            $table->string('status')->default('pending');
            $table->timestamps();
        });
        // The production table has a unique pair. This test table permits a duplicate
        // solely to verify the service's defensive aggregation behavior.
        Schema::create('product_recipes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('variant_id')->constrained('product_variants')->restrictOnDelete();
            $table->foreignId('ingredient_id')->constrained('ingredients')->restrictOnDelete();
            $table->decimal('quantity', 12, 3);
            $table->timestamps();
        });
        Schema::create('inventory_transactions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('ingredient_id')->constrained('ingredients')->restrictOnDelete();
            $table->string('type');
            $table->decimal('quantity', 12, 3);
            $table->decimal('before_quantity', 12, 3);
            $table->decimal('after_quantity', 12, 3);
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->text('note')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });
    }

    private function staff(string $status = 'active'): User
    {
        return User::forceCreate([
            'name' => 'Inventory staff',
            'email' => uniqid('inventory-', true).'@example.test',
            'password' => 'test-password',
            'role' => 'staff',
            'status' => $status,
        ]);
    }

    private function item(User $staff, int $quantity = 1, string $status = 'pending'): OrderItem
    {
        $category = Category::forceCreate(['name' => uniqid('Category-', true)]);
        $product = Product::forceCreate(['category_id' => $category->id, 'name' => 'Latte', 'slug' => uniqid('latte-', true), 'status' => 'available']);
        $variant = ProductVariant::forceCreate(['product_id' => $product->id, 'name' => 'M', 'price' => '20.00', 'status' => 'available']);
        $order = Order::forceCreate([
            'order_code' => uniqid('ORD-', true),
            'user_id' => $staff->id,
            'order_type' => 'takeaway',
            'status' => 'pending',
            'subtotal' => '20.00',
            'discount_amount' => '0.00',
            'tax_amount' => '0.00',
            'total_amount' => '20.00',
            'ordered_at' => now(),
        ]);

        return OrderItem::forceCreate([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'variant_id' => $variant->id,
            'product_name' => $product->name,
            'variant_name' => $variant->name,
            'quantity' => $quantity,
            'unit_price' => '20.00',
            'topping_amount' => '0.00',
            'subtotal' => '20.00',
            'status' => $status,
        ]);
    }

    private function ingredient(string $stock = '10.000', string $status = 'active'): Ingredient
    {
        return Ingredient::forceCreate([
            'name' => uniqid('Ingredient-', true),
            'unit' => 'g',
            'current_stock' => $stock,
            'minimum_stock' => '0.000',
            'cost_price' => '0.00',
            'status' => $status,
        ]);
    }

    private function recipe(OrderItem $item, Ingredient $ingredient, string $quantity): void
    {
        ProductRecipe::forceCreate([
            'variant_id' => $item->variant_id,
            'ingredient_id' => $ingredient->id,
            'quantity' => $quantity,
        ]);
    }

    public function test_start_preparing_consumes_stock_and_creates_an_immutable_ledger(): void
    {
        $staff = $this->staff();
        $item = $this->item($staff, 2);
        $ingredient = $this->ingredient('10.000');
        $this->recipe($item, $ingredient, '2.500');

        app(InventoryService::class)->startPreparing($item, $staff);

        $this->assertSame('preparing', $item->fresh()->status);
        $this->assertSame('5.000', $ingredient->fresh()->current_stock);
        $transaction = InventoryTransaction::sole();
        $this->assertSame('consume', $transaction->type);
        $this->assertSame('5.000', $transaction->quantity);
        $this->assertSame('10.000', $transaction->before_quantity);
        $this->assertSame('5.000', $transaction->after_quantity);
        $this->assertSame('order_item', $transaction->reference_type);
        $this->assertSame($item->id, $transaction->reference_id);
        $this->assertSame($staff->id, $transaction->created_by);
        $this->assertTrue($transaction->reference->is($item));
    }

    public function test_recipe_rows_for_the_same_ingredient_are_defensively_aggregated(): void
    {
        $staff = $this->staff();
        $item = $this->item($staff, 2);
        $ingredient = $this->ingredient('20.000');
        $this->recipe($item, $ingredient, '1.250');
        $this->recipe($item, $ingredient, '0.750');

        app(InventoryService::class)->startPreparing($item, $staff);

        $this->assertSame('16.000', $ingredient->fresh()->current_stock);
        $this->assertDatabaseCount('inventory_transactions', 1);
        $this->assertSame('4.000', InventoryTransaction::sole()->quantity);
    }

    public function test_insufficient_or_inactive_ingredients_roll_back_everything(): void
    {
        $staff = $this->staff();
        $item = $this->item($staff);
        $available = $this->ingredient('5.000');
        $insufficient = $this->ingredient('0.500');
        $this->recipe($item, $available, '2.000');
        $this->recipe($item, $insufficient, '1.000');

        try {
            app(InventoryService::class)->startPreparing($item, $staff);
            $this->fail('Expected the insufficient ingredient to reject the transition.');
        } catch (ValidationException) {
            $this->assertSame('pending', $item->fresh()->status);
            $this->assertSame('5.000', $available->fresh()->current_stock);
            $this->assertSame('0.500', $insufficient->fresh()->current_stock);
            $this->assertDatabaseCount('inventory_transactions', 0);
        }

        $inactiveItem = $this->item($staff);
        $inactiveIngredient = $this->ingredient('5.000', 'inactive');
        $this->recipe($inactiveItem, $inactiveIngredient, '1.000');

        try {
            app(InventoryService::class)->startPreparing($inactiveItem, $staff);
            $this->fail('Expected the inactive ingredient to reject the transition.');
        } catch (ValidationException) {
            $this->assertSame('pending', $inactiveItem->fresh()->status);
            $this->assertSame('5.000', $inactiveIngredient->fresh()->current_stock);
            $this->assertDatabaseCount('inventory_transactions', 0);
        }
    }

    public function test_a_non_pending_item_cannot_consume_stock_twice(): void
    {
        $staff = $this->staff();
        $item = $this->item($staff);
        $ingredient = $this->ingredient('5.000');
        $this->recipe($item, $ingredient, '1.000');
        $service = app(InventoryService::class);

        $service->startPreparing($item, $staff);
        $this->expectException(ValidationException::class);
        try {
            $service->startPreparing($item, $staff);
        } finally {
            $this->assertSame('4.000', $ingredient->fresh()->current_stock);
            $this->assertDatabaseCount('inventory_transactions', 1);
        }
    }

    public function test_service_calculations_do_not_use_float(): void
    {
        $source = file_get_contents(app_path('Services/InventoryService.php'));

        $this->assertStringNotContainsString('float', $source);
    }
}
