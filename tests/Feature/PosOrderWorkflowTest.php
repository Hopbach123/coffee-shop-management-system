<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\CafeTable;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PosOrderWorkflowTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->assertSame('sqlite', config('database.default'));
        foreach ([
            '0001_01_01_000000_create_users_table.php',
            '2026_08_14_000002_create_areas_table.php',
            '2026_08_14_000003_create_categories_table.php',
            '2026_08_14_000006_create_cafe_tables_table.php',
            '2026_08_14_000007_create_products_table.php',
        ] as $migration) {
            (require database_path('migrations/'.$migration))->up();
        }

        Schema::create('product_variants', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->string('name', 50);
            $table->string('sku', 50)->nullable()->unique();
            $table->decimal('price', 12, 2);
            $table->string('status')->default('available');
            $table->softDeletes();
            $table->timestamps();
            $table->unique(['product_id', 'name']);
        });

        Schema::create('orders', function (Blueprint $table): void {
            $table->id();
            $table->string('order_code', 50)->unique();
            $table->foreignId('table_id')->nullable()->constrained('cafe_tables')->restrictOnDelete();
            $table->foreignId('customer_id')->nullable();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('reservation_id')->nullable();
            $table->string('order_type');
            $table->string('status')->default('pending');
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->text('note')->nullable();
            $table->dateTime('ordered_at');
            $table->dateTime('completed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('order_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->restrictOnDelete();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->foreignId('variant_id')->constrained('product_variants')->restrictOnDelete();
            $table->string('product_name');
            $table->string('variant_name', 50)->nullable();
            $table->integer('quantity');
            $table->decimal('unit_price', 12, 2);
            $table->decimal('topping_amount', 12, 2)->default(0);
            $table->decimal('subtotal', 15, 2);
            $table->string('note', 500)->nullable();
            $table->string('status')->default('pending');
            $table->timestamps();
        });

        $this->withoutVite();
    }

    private function staff(string $status = 'active', string $role = 'staff'): User
    {
        return User::forceCreate([
            'name' => 'POS Staff',
            'email' => uniqid('pos-', true).'@example.test',
            'password' => 'test-only-password',
            'role' => $role,
            'status' => $status,
        ]);
    }

    private function variant(string $status = 'available'): ProductVariant
    {
        $category = Category::create(['name' => uniqid('Category-', true)]);
        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Latte',
            'slug' => uniqid('latte-', true),
            'status' => 'available',
        ]);

        return ProductVariant::forceCreate([
            'product_id' => $product->id,
            'name' => 'Lớn',
            'sku' => uniqid('SKU-', true),
            'price' => '35.50',
            'status' => $status,
        ]);
    }

    private function table(string $status = 'available'): CafeTable
    {
        $area = Area::create(['name' => uniqid('Area-', true), 'status' => 'active']);

        return CafeTable::forceCreate([
            'area_id' => $area->id,
            'table_code' => strtoupper(uniqid('T', false)),
            'table_name' => 'Bàn POS',
            'capacity' => 4,
            'status' => $status,
        ]);
    }

    public function test_active_staff_can_checkout_a_takeaway_cart_through_order_service(): void
    {
        $staff = $this->staff();
        $variant = $this->variant();

        $this->actingAs($staff, 'web')->post(route('staff.pos.add'), ['variant_id' => $variant->id])
            ->assertRedirect(route('staff.home'));
        $this->post(route('staff.pos.checkout'), [
            'order_type' => 'takeaway',
            'user_id' => 999,
            'status' => 'completed',
            'order_code' => 'FORGED',
            'subtotal' => '999.99',
            'total_amount' => '999.99',
        ])->assertRedirect(route('staff.orders.show', 1))->assertSessionHas('success')->assertSessionMissing('pos_cart');

        $order = Order::sole();
        $this->assertSame($staff->id, $order->user_id);
        $this->assertSame('takeaway', $order->order_type);
        $this->assertSame('pending', $order->status);
        $this->assertNull($order->table_id);
        $this->assertNotSame('FORGED', $order->order_code);
        $this->assertSame('35.50', $order->subtotal);
        $this->assertSame('35.50', $order->total_amount);
        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->id,
            'variant_id' => $variant->id,
            'quantity' => 1,
            'unit_price' => '35.50',
            'subtotal' => '35.50',
            'status' => 'pending',
        ]);
    }

    public function test_dine_in_checkout_uses_the_order_service_table_workflow(): void
    {
        $staff = $this->staff();
        $variant = $this->variant();
        $table = $this->table();

        $this->actingAs($staff, 'web')->withSession(['pos_cart' => [$variant->id => 2]])
            ->post(route('staff.pos.checkout'), ['order_type' => 'dine_in', 'table_id' => $table->id])
            ->assertRedirect(route('staff.orders.show', 1));

        $order = Order::sole();
        $this->assertSame($table->id, $order->table_id);
        $this->assertSame('71.00', $order->total_amount);
        $this->assertSame('occupied', $table->fresh()->status);
    }

    public function test_invalid_cart_variant_or_table_rolls_back_and_preserves_the_cart(): void
    {
        $staff = $this->staff();
        $variant = $this->variant();
        $occupiedTable = $this->table('occupied');
        $this->actingAs($staff, 'web')->withSession(['pos_cart' => [$variant->id => 1]])
            ->postJson(route('staff.pos.checkout'), ['order_type' => 'dine_in', 'table_id' => $occupiedTable->id])
            ->assertUnprocessable()->assertJsonValidationErrors('table_id')->assertSessionHas('pos_cart');
        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('order_items', 0);

        $variant->forceFill(['status' => 'unavailable'])->save();
        $this->postJson(route('staff.pos.checkout'), ['order_type' => 'takeaway'])
            ->assertUnprocessable()->assertJsonValidationErrors('variant_id')->assertSessionHas('pos_cart');
        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('order_items', 0);
    }

    public function test_active_admin_can_create_order_from_pos_and_cancel_while_pending(): void
    {
        $admin = $this->staff('active', 'admin');
        $variant = $this->variant();
        $this->actingAs($admin, 'web')->get(route('staff.home'))->assertOk();
        $this->post(route('staff.pos.add'), ['variant_id' => $variant->id])->assertSessionHasNoErrors();
        $this->post(route('staff.pos.checkout'), ['order_type' => 'takeaway'])->assertSessionHasNoErrors();
        $order = Order::firstOrFail();
        $this->assertSame($admin->id, $order->user_id);
        $this->assertDatabaseCount('order_items', 1);
        $this->patch(route('staff.orders.cancel', $order))->assertSessionHasNoErrors();
        $this->assertSame('cancelled', $order->fresh()->status);
    }

    public function test_guests_and_inactive_users_cannot_operate_pos(): void
    {
        $variant = $this->variant();
        $this->get(route('staff.home'))->assertRedirect(route('login'));
        $this->post(route('staff.pos.add'), ['variant_id' => $variant->id])->assertRedirect(route('login'));

        $admin = $this->staff('inactive', 'admin');
        $this->actingAs($admin, 'web')->get(route('staff.home'))->assertForbidden();
        $this->post(route('staff.pos.add'), ['variant_id' => $variant->id])->assertForbidden();

        $inactiveStaff = $this->staff('inactive');
        $this->actingAs($inactiveStaff, 'web')->get(route('staff.home'))->assertForbidden();
        $this->post(route('staff.pos.checkout'), ['order_type' => 'takeaway'])->assertForbidden();
    }

    public function test_pos_controller_does_not_contain_legacy_order_or_money_persistence_logic(): void
    {
        $source = file_get_contents(app_path('Http/Controllers/Staff/PosController.php'));

        foreach (['DB::transaction', 'round(', '(float)', 'createMany', 'new Order'] as $forbidden) {
            $this->assertStringNotContainsString($forbidden, $source);
        }
    }
}
