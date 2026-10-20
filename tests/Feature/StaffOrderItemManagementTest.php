<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Ingredient;
use App\Models\InventoryTransaction;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderItemTopping;
use App\Models\Product;
use App\Models\ProductRecipe;
use App\Models\ProductVariant;
use App\Models\Topping;
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
        (require database_path('migrations/2026_08_14_000004_create_toppings_table.php'))->up();
        (require database_path('migrations/2026_08_14_000005_create_ingredients_table.php'))->up();
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

        Schema::create('order_item_toppings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_item_id')->constrained('order_items')->restrictOnDelete();
            $table->foreignId('topping_id')->constrained('toppings')->restrictOnDelete();
            $table->string('topping_name');
            $table->decimal('price', 12, 2);
            $table->integer('quantity')->default(1);
            $table->timestamps();
            $table->unique(['order_item_id', 'topping_id']);
        });

        Schema::create('product_recipes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('variant_id')->constrained('product_variants')->restrictOnDelete();
            $table->foreignId('ingredient_id')->constrained('ingredients')->restrictOnDelete();
            $table->decimal('quantity', 12, 3);
            $table->timestamps();
            $table->unique(['variant_id', 'ingredient_id']);
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

    private function item(Order $order, ProductVariant $variant, int $quantity = 1, string $toppingAmount = '0.00', string $status = 'pending'): OrderItem
    {
        $unitPrice = (string) $variant->price;
        $subtotalInCents = ($this->moneyToCents($unitPrice) * $quantity) + $this->moneyToCents($toppingAmount);
        $subtotal = intdiv($subtotalInCents, 100).'.'.str_pad((string) ($subtotalInCents % 100), 2, '0', STR_PAD_LEFT);

        return OrderItem::forceCreate([
            'order_id' => $order->id,
            'product_id' => $variant->product_id,
            'variant_id' => $variant->id,
            'product_name' => $variant->product->name,
            'variant_name' => $variant->name,
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'topping_amount' => $toppingAmount,
            'subtotal' => $subtotal,
            'status' => $status,
        ]);
    }

    private function moneyToCents(string $amount): int
    {
        [$whole, $fraction] = explode('.', $amount);

        return ((int) $whole * 100) + (int) str_pad($fraction, 2, '0');
    }

    private function topping(string $price = '2.00', string $status = 'available'): Topping
    {
        return Topping::forceCreate([
            'name' => uniqid('Topping-', true),
            'price' => $price,
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

    public function test_active_staff_can_update_a_pending_item_quantity_and_recalculate_snapshot_totals(): void
    {
        $staff = $this->staff();
        $order = $this->order($staff);
        $variant = $this->sellableVariant();
        $item = $this->item($order, $variant, 2, '5.00');

        $this->actingAs($staff, 'web')->patch(route('staff.orders.items.update', [$order, $item]), [
            'quantity' => 3,
            'unit_price' => '999.99',
            'topping_amount' => '999.99',
            'subtotal' => '999.99',
            'status' => 'served',
            'order_id' => 999,
        ])->assertRedirect(route('staff.orders.show', $order))->assertSessionHas('success');

        $item->refresh();
        $this->assertSame(3, $item->quantity);
        $this->assertSame('12.50', $item->unit_price);
        $this->assertSame('5.00', $item->topping_amount);
        $this->assertSame('42.50', $item->subtotal);
        $this->assertSame('pending', $item->status);
        $this->assertSame($order->id, $item->order_id);
        $this->assertSame('42.50', $order->fresh()->subtotal);
        $this->assertSame('42.50', $order->fresh()->total_amount);
    }

    public function test_quantity_update_rejects_invalid_values_and_item_from_another_order(): void
    {
        $staff = $this->staff();
        $variant = $this->sellableVariant();
        $order = $this->order($staff);
        $item = $this->item($order, $variant);

        $this->actingAs($staff, 'web');
        foreach ([0, -1, '1.5'] as $quantity) {
            $this->patchJson(route('staff.orders.items.update', [$order, $item]), ['quantity' => $quantity])
                ->assertUnprocessable()->assertJsonValidationErrors('quantity');
        }

        $otherOrder = $this->order($staff);
        $otherItem = $this->item($otherOrder, $variant);
        $this->patchJson(route('staff.orders.items.update', [$order, $otherItem]), ['quantity' => 2])
            ->assertUnprocessable()->assertJsonValidationErrors('order_item');

        $this->assertSame(1, $item->fresh()->quantity);
        $this->assertSame(1, $otherItem->fresh()->quantity);
    }

    public function test_non_pending_orders_or_items_cannot_be_updated_or_removed(): void
    {
        $staff = $this->staff();
        $variant = $this->sellableVariant();
        $order = $this->order($staff);
        $item = $this->item($order, $variant, 1, '0.00', 'preparing');
        $completedOrder = $this->order($staff, 'completed');
        $completedOrderItem = $this->item($completedOrder, $variant);

        $this->actingAs($staff, 'web');
        $this->patchJson(route('staff.orders.items.update', [$order, $item]), ['quantity' => 2])
            ->assertUnprocessable()->assertJsonValidationErrors('order_item');
        $this->deleteJson(route('staff.orders.items.destroy', [$order, $item]))
            ->assertUnprocessable()->assertJsonValidationErrors('order_item');
        $this->patchJson(route('staff.orders.items.update', [$completedOrder, $completedOrderItem]), ['quantity' => 2])
            ->assertUnprocessable()->assertJsonValidationErrors('order');
        $this->deleteJson(route('staff.orders.items.destroy', [$completedOrder, $completedOrderItem]))
            ->assertUnprocessable()->assertJsonValidationErrors('order');

        $this->assertDatabaseCount('order_items', 2);
        $this->assertSame(1, $item->fresh()->quantity);
    }

    public function test_active_staff_can_remove_pending_items_and_recalculate_order_to_zero(): void
    {
        $staff = $this->staff();
        $order = $this->order($staff);
        $variant = $this->sellableVariant();
        $firstItem = $this->item($order, $variant, 2);
        $secondItem = $this->item($order, $variant, 1);

        $this->actingAs($staff, 'web')->delete(route('staff.orders.items.destroy', [$order, $firstItem]))
            ->assertRedirect(route('staff.orders.show', $order))->assertSessionHas('success');
        $this->assertDatabaseMissing('order_items', ['id' => $firstItem->id]);
        $this->assertSame('12.50', $order->fresh()->subtotal);

        $this->delete(route('staff.orders.items.destroy', [$order, $secondItem]))
            ->assertRedirect(route('staff.orders.show', $order));
        $this->assertDatabaseMissing('order_items', ['id' => $secondItem->id]);
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'pending', 'subtotal' => '0.00', 'total_amount' => '0.00']);
    }

    public function test_guest_admin_and_inactive_staff_cannot_update_or_remove_order_items(): void
    {
        $staff = $this->staff();
        $order = $this->order($staff);
        $item = $this->item($order, $this->sellableVariant());
        $updateRoute = route('staff.orders.items.update', [$order, $item]);
        $deleteRoute = route('staff.orders.items.destroy', [$order, $item]);

        $this->patch($updateRoute, ['quantity' => 2])->assertRedirect(route('login'));
        $this->delete($deleteRoute)->assertRedirect(route('login'));
        $this->actingAs($this->staff('active', 'admin'), 'web')->patch($updateRoute, ['quantity' => 2])->assertForbidden();
        $this->actingAs($this->staff('inactive'), 'web')->delete($deleteRoute)->assertForbidden();
        $this->assertDatabaseHas('order_items', ['id' => $item->id, 'quantity' => 1]);
    }

    public function test_update_and_delete_roll_back_when_order_total_save_fails(): void
    {
        $staff = $this->staff();
        $order = $this->order($staff);
        $item = $this->item($order, $this->sellableVariant());
        Order::saving(fn () => throw new QueryException('sqlite', 'private SQL', [], new \RuntimeException('private failure')));

        try {
            $this->actingAs($staff, 'web')->from(route('staff.orders.show', $order))
                ->patch(route('staff.orders.items.update', [$order, $item]), ['quantity' => 2])
                ->assertRedirect(route('staff.orders.show', $order))->assertSessionHasErrors('order');
            $this->assertSame(1, $item->fresh()->quantity);

            $this->delete(route('staff.orders.items.destroy', [$order, $item]))
                ->assertRedirect(route('staff.orders.show', $order))->assertSessionHasErrors('order');
            $this->assertDatabaseHas('order_items', ['id' => $item->id]);
        } finally {
            Order::flushEventListeners();
        }
    }

    public function test_active_staff_can_add_topping_with_price_and_name_snapshots_and_recalculated_totals(): void
    {
        $staff = $this->staff();
        $order = $this->order($staff);
        $item = $this->item($order, $this->sellableVariant(), 2);
        $topping = $this->topping('2.50');

        $this->actingAs($staff, 'web')->post(route('staff.orders.items.toppings.store', [$order, $item]), [
            'topping_id' => $topping->id,
            'quantity' => 3,
            'price' => '999.99',
            'topping_name' => 'Không được tin',
            'subtotal' => '999.99',
            'status' => 'served',
            'order_id' => 999,
        ])->assertRedirect(route('staff.orders.show', $order))->assertSessionHas('success');

        $itemTopping = OrderItemTopping::sole();
        $this->assertSame($item->id, $itemTopping->order_item_id);
        $this->assertSame($topping->id, $itemTopping->topping_id);
        $this->assertSame($topping->name, $itemTopping->topping_name);
        $this->assertSame('2.50', $itemTopping->price);
        $this->assertSame(3, $itemTopping->quantity);
        $this->assertSame('7.50', $item->fresh()->topping_amount);
        $this->assertSame('32.50', $item->fresh()->subtotal);
        $this->assertSame('32.50', $order->fresh()->subtotal);
        $this->assertSame('32.50', $order->fresh()->total_amount);
    }

    public function test_adding_same_topping_aggregates_its_quantity_without_duplicate_row_or_new_price_snapshot(): void
    {
        $staff = $this->staff();
        $order = $this->order($staff);
        $item = $this->item($order, $this->sellableVariant());
        $topping = $this->topping('2.00');

        $this->actingAs($staff, 'web')->post(route('staff.orders.items.toppings.store', [$order, $item]), ['topping_id' => $topping->id, 'quantity' => 2]);
        $topping->forceFill(['price' => '9.00', 'name' => 'Đã đổi tên'])->save();
        $this->post(route('staff.orders.items.toppings.store', [$order, $item]), ['topping_id' => $topping->id, 'quantity' => 3])
            ->assertRedirect(route('staff.orders.show', $order));

        $this->assertDatabaseCount('order_item_toppings', 1);
        $itemTopping = OrderItemTopping::sole();
        $this->assertSame(5, $itemTopping->quantity);
        $this->assertSame('2.00', $itemTopping->price);
        $this->assertNotSame('Đã đổi tên', $itemTopping->topping_name);
        $this->assertSame('10.00', $item->fresh()->topping_amount);
        $this->assertSame('22.50', $item->fresh()->subtotal);
    }

    public function test_unavailable_topping_is_rejected_without_changes(): void
    {
        $staff = $this->staff();
        $order = $this->order($staff);
        $item = $this->item($order, $this->sellableVariant());
        $topping = $this->topping('2.00', 'unavailable');

        $this->actingAs($staff, 'web')->postJson(route('staff.orders.items.toppings.store', [$order, $item]), ['topping_id' => $topping->id, 'quantity' => 1])
            ->assertUnprocessable()->assertJsonValidationErrors('topping_id');

        $this->assertDatabaseCount('order_item_toppings', 0);
        $this->assertSame('0.00', $item->fresh()->topping_amount);
    }

    public function test_active_staff_can_remove_topping_and_recalculate_item_and_order_totals(): void
    {
        $staff = $this->staff();
        $order = $this->order($staff);
        $item = $this->item($order, $this->sellableVariant());
        $first = $this->topping('2.00');
        $second = $this->topping('3.50');
        $this->actingAs($staff, 'web');
        $this->post(route('staff.orders.items.toppings.store', [$order, $item]), ['topping_id' => $first->id, 'quantity' => 2]);
        $this->post(route('staff.orders.items.toppings.store', [$order, $item]), ['topping_id' => $second->id, 'quantity' => 1]);
        $firstRow = OrderItemTopping::where('topping_id', $first->id)->sole();

        $this->delete(route('staff.orders.items.toppings.destroy', [$order, $item, $firstRow]))
            ->assertRedirect(route('staff.orders.show', $order))->assertSessionHas('success');

        $this->assertDatabaseMissing('order_item_toppings', ['id' => $firstRow->id]);
        $this->assertSame('3.50', $item->fresh()->topping_amount);
        $this->assertSame('16.00', $item->fresh()->subtotal);
        $this->assertSame('16.00', $order->fresh()->total_amount);
    }

    public function test_topping_operations_are_rejected_for_non_pending_order_or_item_and_wrong_item_url(): void
    {
        $staff = $this->staff();
        $variant = $this->sellableVariant();
        $topping = $this->topping();
        $pendingOrder = $this->order($staff);
        $pendingItem = $this->item($pendingOrder, $variant);
        $otherItem = $this->item($pendingOrder, $variant);
        $otherRow = OrderItemTopping::forceCreate([
            'order_item_id' => $otherItem->id,
            'topping_id' => $topping->id,
            'topping_name' => $topping->name,
            'price' => '2.00',
            'quantity' => 1,
        ]);
        $preparingItem = $this->item($pendingOrder, $variant, 1, '0.00', 'preparing');
        $completedOrder = $this->order($staff, 'completed');
        $completedItem = $this->item($completedOrder, $variant);

        $this->actingAs($staff, 'web');
        $this->postJson(route('staff.orders.items.toppings.store', [$pendingOrder, $preparingItem]), ['topping_id' => $topping->id, 'quantity' => 1])
            ->assertUnprocessable()->assertJsonValidationErrors('order_item');
        $this->postJson(route('staff.orders.items.toppings.store', [$completedOrder, $completedItem]), ['topping_id' => $topping->id, 'quantity' => 1])
            ->assertUnprocessable()->assertJsonValidationErrors('order');
        $this->deleteJson(route('staff.orders.items.toppings.destroy', [$pendingOrder, $pendingItem, $otherRow]))
            ->assertUnprocessable()->assertJsonValidationErrors('order_item_topping');

        $this->assertDatabaseHas('order_item_toppings', ['id' => $otherRow->id]);
    }

    public function test_guest_admin_and_inactive_staff_cannot_manage_toppings(): void
    {
        $staff = $this->staff();
        $order = $this->order($staff);
        $item = $this->item($order, $this->sellableVariant());
        $topping = $this->topping();
        $storeRoute = route('staff.orders.items.toppings.store', [$order, $item]);

        $this->post($storeRoute, ['topping_id' => $topping->id, 'quantity' => 1])->assertRedirect(route('login'));
        $this->actingAs($this->staff('active', 'admin'), 'web')->post($storeRoute, ['topping_id' => $topping->id, 'quantity' => 1])->assertForbidden();
        $this->actingAs($this->staff('inactive'), 'web')->post($storeRoute, ['topping_id' => $topping->id, 'quantity' => 1])->assertForbidden();
        $this->assertDatabaseCount('order_item_toppings', 0);
    }

    public function test_topping_changes_roll_back_when_order_total_save_fails(): void
    {
        $staff = $this->staff();
        $order = $this->order($staff);
        $item = $this->item($order, $this->sellableVariant());
        $topping = $this->topping();
        Order::saving(fn () => throw new QueryException('sqlite', 'private SQL', [], new \RuntimeException('private failure')));

        try {
            $this->actingAs($staff, 'web')->from(route('staff.orders.show', $order))
                ->post(route('staff.orders.items.toppings.store', [$order, $item]), ['topping_id' => $topping->id, 'quantity' => 1])
                ->assertRedirect(route('staff.orders.show', $order))->assertSessionHasErrors('order');
            $this->assertDatabaseCount('order_item_toppings', 0);
            $this->assertSame('0.00', $item->fresh()->topping_amount);
        } finally {
            Order::flushEventListeners();
        }
    }

    public function test_topping_removal_rolls_back_when_order_total_save_fails(): void
    {
        $staff = $this->staff();
        $order = $this->order($staff);
        $item = $this->item($order, $this->sellableVariant(), 1, '2.00');
        $order->forceFill(['subtotal' => '14.50', 'total_amount' => '14.50'])->save();
        $topping = $this->topping('2.00');
        $itemTopping = OrderItemTopping::forceCreate([
            'order_item_id' => $item->id,
            'topping_id' => $topping->id,
            'topping_name' => $topping->name,
            'price' => '2.00',
            'quantity' => 1,
        ]);
        Order::saving(fn () => throw new QueryException('sqlite', 'private SQL', [], new \RuntimeException('private failure')));

        try {
            $this->actingAs($staff, 'web')->from(route('staff.orders.show', $order))
                ->delete(route('staff.orders.items.toppings.destroy', [$order, $item, $itemTopping]))
                ->assertRedirect(route('staff.orders.show', $order))->assertSessionHasErrors('order');
            $this->assertDatabaseHas('order_item_toppings', ['id' => $itemTopping->id]);
            $this->assertSame('2.00', $item->fresh()->topping_amount);
        } finally {
            Order::flushEventListeners();
        }
    }

    public function test_active_staff_can_update_a_pending_topping_quantity_and_recalculate_totals(): void
    {
        $staff = $this->staff();
        $order = $this->order($staff);
        $item = $this->item($order, $this->sellableVariant(), 2, '2.00');
        $order->forceFill(['subtotal' => '27.00', 'total_amount' => '27.00'])->save();
        $topping = $this->topping('2.00');
        $itemTopping = OrderItemTopping::forceCreate([
            'order_item_id' => $item->id,
            'topping_id' => $topping->id,
            'topping_name' => $topping->name,
            'price' => '2.00',
            'quantity' => 1,
        ]);

        $this->actingAs($staff, 'web')->patch(route('staff.orders.items.toppings.update', [$order, $item, $itemTopping]), [
            'quantity' => 3,
            'price' => '999.99',
            'topping_id' => 999,
        ])->assertRedirect(route('staff.orders.show', $order))->assertSessionHas('success');

        $this->assertSame(3, $itemTopping->fresh()->quantity);
        $this->assertSame('2.00', $itemTopping->fresh()->price);
        $this->assertSame('6.00', $item->fresh()->topping_amount);
        $this->assertSame('31.00', $item->fresh()->subtotal);
        $this->assertSame('31.00', $order->fresh()->total_amount);
    }

    public function test_active_staff_can_start_preparing_and_inventory_is_consumed_once(): void
    {
        $staff = $this->staff();
        $order = $this->order($staff);
        $item = $this->item($order, $this->sellableVariant(), 2);
        $ingredient = $this->ingredient('8.000');
        $this->recipe($item, $ingredient, '1.500');
        $route = route('staff.orders.items.status.update', [$order, $item]);

        $this->actingAs($staff, 'web')->patch($route, [
            'status' => 'preparing',
            'quantity' => 999,
            'unit_price' => '999.99',
        ])->assertRedirect(route('staff.orders.show', $order))->assertSessionHas('success');

        $this->assertSame('preparing', $item->fresh()->status);
        $this->assertSame(2, $item->fresh()->quantity);
        $this->assertSame('5.000', $ingredient->fresh()->current_stock);
        $this->assertDatabaseHas('inventory_transactions', [
            'ingredient_id' => $ingredient->id,
            'type' => 'consume',
            'quantity' => '3.000',
            'before_quantity' => '8.000',
            'after_quantity' => '5.000',
            'reference_type' => 'order_item',
            'reference_id' => $item->id,
            'created_by' => $staff->id,
        ]);

        $this->patchJson($route, ['status' => 'preparing'])
            ->assertUnprocessable()->assertJsonValidationErrors('order_item');
        $this->assertSame('5.000', $ingredient->fresh()->current_stock);
        $this->assertDatabaseCount('inventory_transactions', 1);
    }

    public function test_insufficient_stock_rolls_back_pending_to_preparing_transition(): void
    {
        $staff = $this->staff();
        $order = $this->order($staff);
        $item = $this->item($order, $this->sellableVariant());
        $ingredient = $this->ingredient('0.500');
        $this->recipe($item, $ingredient, '1.000');

        $this->actingAs($staff, 'web')->patchJson(route('staff.orders.items.status.update', [$order, $item]), ['status' => 'preparing'])
            ->assertUnprocessable()->assertJsonValidationErrors('ingredient');

        $this->assertSame('pending', $item->fresh()->status);
        $this->assertSame('0.500', $ingredient->fresh()->current_stock);
        $this->assertDatabaseCount('inventory_transactions', 0);
    }

    public function test_status_transition_allows_only_the_approved_forward_lifecycle(): void
    {
        $staff = $this->staff();
        $order = $this->order($staff);
        $item = $this->item($order, $this->sellableVariant());
        $ingredient = $this->ingredient('4.000');
        $this->recipe($item, $ingredient, '1.000');
        $route = route('staff.orders.items.status.update', [$order, $item]);

        $this->actingAs($staff, 'web');
        $this->patchJson($route, ['status' => 'ready'])->assertUnprocessable()->assertJsonValidationErrors('status');
        $this->patch($route, ['status' => 'preparing'])->assertRedirect(route('staff.orders.show', $order));
        $this->patch($route, ['status' => 'ready'])->assertRedirect(route('staff.orders.show', $order));
        $this->patch($route, ['status' => 'served'])->assertRedirect(route('staff.orders.show', $order));
        $this->patchJson($route, ['status' => 'preparing'])->assertUnprocessable()->assertJsonValidationErrors('order_item');

        $this->assertSame('served', $item->fresh()->status);
        $this->assertSame('3.000', $ingredient->fresh()->current_stock);
        $this->assertDatabaseCount('inventory_transactions', 1);
    }

    public function test_status_transition_rejects_wrong_order_non_pending_order_and_unauthorized_actors(): void
    {
        $staff = $this->staff();
        $order = $this->order($staff);
        $otherItem = $this->item($this->order($staff), $this->sellableVariant());
        $item = $this->item($order, $this->sellableVariant());
        $authorizedRoute = route('staff.orders.items.status.update', [$order, $item]);
        $this->patch($authorizedRoute, ['status' => 'preparing'])->assertRedirect(route('login'));

        $route = route('staff.orders.items.status.update', [$order, $otherItem]);

        $this->actingAs($staff, 'web')->patchJson($route, ['status' => 'preparing'])
            ->assertUnprocessable()->assertJsonValidationErrors('order_item');

        $completedOrder = $this->order($staff, 'completed');
        $completedItem = $this->item($completedOrder, $this->sellableVariant());
        $this->patchJson(route('staff.orders.items.status.update', [$completedOrder, $completedItem]), ['status' => 'preparing'])
            ->assertUnprocessable()->assertJsonValidationErrors('order');

        $this->actingAs($this->staff('active', 'admin'), 'web')->patch($authorizedRoute, ['status' => 'preparing'])->assertForbidden();
        $this->actingAs($this->staff('inactive'), 'web')->patch($authorizedRoute, ['status' => 'preparing'])->assertForbidden();
    }
}
