<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class StaffOrderItemManagementTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->assertSame('sqlite', config('database.default'));
        (require database_path('migrations/0001_01_01_000000_create_users_table.php'))->up();
        (require database_path('migrations/2026_08_14_000003_create_categories_table.php'))->up();
        (require database_path('migrations/2026_08_14_000007_create_products_table.php'))->up();

        // The production migrations add MySQL-only CHECK constraints after create.
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
            $table->foreignId('table_id')->nullable();
            $table->foreignId('customer_id')->nullable();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('reservation_id')->nullable();
            $table->string('order_type')->default('takeaway');
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
            'name' => 'Staff',
            'email' => uniqid('staff-', true).'@example.test',
            'password' => 'test-only-password',
            'role' => $role,
            'status' => $status,
        ]);
    }

    private function sellableVariant(string $price = '12.50'): ProductVariant
    {
        $category = Category::create(['name' => uniqid('Coffee-', true)]);
        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Latte',
            'slug' => uniqid('latte-', true),
        ]);

        return ProductVariant::create([
            'product_id' => $product->id,
            'name' => 'Lớn',
            'sku' => uniqid('SKU-', true),
            'price' => $price,
        ]);
    }

    private function order(User $staff, string $status = 'pending'): Order
    {
        return Order::forceCreate([
            'order_code' => uniqid('ORD-', true),
            'user_id' => $staff->id,
            'order_type' => 'takeaway',
            'status' => $status,
            'subtotal' => '0.00',
            'discount_amount' => '0.00',
            'tax_amount' => '0.00',
            'total_amount' => '0.00',
            'ordered_at' => now(),
        ]);
    }

    public function test_active_staff_can_add_available_variant_with_snapshots_and_recalculated_totals(): void
    {
        $staff = $this->staff();
        $order = $this->order($staff);
        $variant = $this->sellableVariant();

        $this->actingAs($staff, 'web')->post(route('staff.orders.items.store', $order), [
            'variant_id' => $variant->id,
            'quantity' => 3,
            'note' => '  Ít đá  ',
        ])->assertRedirect(route('staff.orders.show', $order))->assertSessionHas('success');

        $item = OrderItem::sole();
        $this->assertSame($order->id, $item->order_id);
        $this->assertSame($variant->product_id, $item->product_id);
        $this->assertSame($variant->id, $item->variant_id);
        $this->assertSame('Latte', $item->product_name);
        $this->assertSame('Lớn', $item->variant_name);
        $this->assertSame(3, $item->quantity);
        $this->assertSame('12.50', $item->unit_price);
        $this->assertSame('0.00', $item->topping_amount);
        $this->assertSame('37.50', $item->subtotal);
        $this->assertSame('Ít đá', $item->note);
        $this->assertSame('pending', $item->status);
        $this->assertSame('37.50', $order->fresh()->subtotal);
        $this->assertSame('37.50', $order->fresh()->total_amount);

        $variant->forceFill(['price' => '99.00', 'name' => 'Đổi tên'])->save();
        $this->assertSame('12.50', $item->fresh()->unit_price);
        $this->assertSame('Lớn', $item->fresh()->variant_name);
    }

    public function test_unavailable_or_non_pending_resources_are_rejected_without_item_or_total_change(): void
    {
        $staff = $this->staff();
        $order = $this->order($staff);
        $variant = $this->sellableVariant();
        $variant->forceFill(['status' => 'unavailable'])->save();

        $this->actingAs($staff, 'web')->postJson(route('staff.orders.items.store', $order), ['variant_id' => $variant->id, 'quantity' => 1])
            ->assertUnprocessable()->assertJsonValidationErrors('variant_id');

        $variant->forceFill(['status' => 'available'])->save();
        $order->forceFill(['status' => 'completed'])->save();
        $this->postJson(route('staff.orders.items.store', $order), ['variant_id' => $variant->id, 'quantity' => 1])
            ->assertUnprocessable()->assertJsonValidationErrors('order');

        $this->assertDatabaseCount('order_items', 0);
        $this->assertSame('0.00', $order->fresh()->subtotal);
    }

    public function test_quantity_must_be_a_positive_integer(): void
    {
        $staff = $this->staff();
        $order = $this->order($staff);
        $variant = $this->sellableVariant();
        $this->actingAs($staff, 'web');

        foreach ([0, -1, '1.5'] as $quantity) {
            $this->postJson(route('staff.orders.items.store', $order), ['variant_id' => $variant->id, 'quantity' => $quantity])
                ->assertUnprocessable()->assertJsonValidationErrors('quantity');
        }

        $this->assertDatabaseCount('order_items', 0);
    }

    public function test_item_and_totals_roll_back_if_order_save_fails(): void
    {
        $staff = $this->staff();
        $order = $this->order($staff);
        $variant = $this->sellableVariant();
        Order::saving(fn () => throw new QueryException('sqlite', 'private SQL', [], new \RuntimeException('private failure')));

        try {
            $this->actingAs($staff, 'web')->from(route('staff.orders.show', $order))
                ->post(route('staff.orders.items.store', $order), ['variant_id' => $variant->id, 'quantity' => 2])
                ->assertRedirect(route('staff.orders.show', $order))->assertSessionHasErrors('order');
            $this->assertDatabaseCount('order_items', 0);
            $this->assertSame('0.00', $order->fresh()->subtotal);
        } finally {
            Order::flushEventListeners();
        }
    }

    public function test_guest_admin_and_inactive_staff_cannot_add_order_items(): void
    {
        $staff = $this->staff();
        $order = $this->order($staff);
        $variant = $this->sellableVariant();
        $payload = ['variant_id' => $variant->id, 'quantity' => 1];

        $this->post(route('staff.orders.items.store', $order), $payload)->assertRedirect(route('login'));
        $this->actingAs($this->staff('active', 'admin'), 'web')->post(route('staff.orders.items.store', $order), $payload)->assertForbidden();
        $this->actingAs($this->staff('inactive'), 'web')->post(route('staff.orders.items.store', $order), $payload)->assertForbidden();
        $this->assertDatabaseCount('order_items', 0);
    }
}
