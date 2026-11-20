<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\CafeTable;
use App\Models\Category;
use App\Models\Ingredient;
use App\Models\InventoryTransaction;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductRecipe;
use App\Models\ProductVariant;
use App\Models\Topping;
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
            '2026_08_14_000004_create_toppings_table.php',
            '2026_08_14_000005_create_ingredients_table.php',
            '2026_08_14_000006_create_cafe_tables_table.php',
            '2026_08_14_000007_create_products_table.php',
            '2026_08_14_000008_create_promotions_table.php',
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
        Schema::create('payments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id')->constrained('orders');
            $table->string('payment_code')->unique();
            $table->string('payment_method');
            $table->decimal('amount', 15, 2);
            $table->string('status');
            $table->string('transaction_code', 100)->nullable();
            $table->dateTime('paid_at')->nullable();
            $table->foreignId('created_by')->constrained('users');
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
        (require database_path('migrations/2026_10_27_000001_add_pos_checkout_keys_to_orders_table.php'))->up();
        Schema::create('order_item_toppings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_item_id')->constrained('order_items');
            $table->foreignId('topping_id')->constrained('toppings');
            $table->string('topping_name');
            $table->decimal('price', 12, 2);
            $table->integer('quantity');
            $table->unique(['order_item_id', 'topping_id']);
            $table->timestamps();
        });
        Schema::create('order_promotions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id')->unique()->constrained('orders');
            $table->foreignId('promotion_id')->constrained('promotions');
            $table->string('promotion_code');
            $table->decimal('discount_amount', 15, 2);
            $table->timestamps();
        });
        Schema::create('product_recipes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('variant_id')->constrained('product_variants');
            $table->foreignId('ingredient_id')->constrained('ingredients');
            $table->decimal('quantity', 12, 3);
            $table->timestamps();
        });
        Schema::create('inventory_transactions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('ingredient_id')->constrained('ingredients');
            $table->string('type');
            $table->decimal('quantity', 12, 3);
            $table->decimal('before_quantity', 12, 3);
            $table->decimal('after_quantity', 12, 3);
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->text('note')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });

        (require database_path('migrations/2026_10_27_000002_add_delivery_fee_to_orders_table.php'))->up();
        (require database_path('migrations/2026_10_27_000003_create_customer_order_requests_tables.php'))->up();
        (require database_path('migrations/2026_11_18_000001_add_finance_receipt_keys_to_payments_table.php'))->up();
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

    private function checkoutPayload(array $overrides = []): array
    {
        return array_replace(['order_type' => 'takeaway', 'payment_method' => 'cash',
            'received_confirmation' => '1', 'checkout_key' => session('pos_checkout_attempt.key')], $overrides);
    }

    private function recipe(ProductVariant $variant): void
    {
        $ingredient = Ingredient::forceCreate(['name' => 'POS coffee', 'unit' => 'g', 'current_stock' => '100.000', 'status' => 'active']);
        ProductRecipe::forceCreate(['variant_id' => $variant->id, 'ingredient_id' => $ingredient->id, 'quantity' => '1.500']);
    }

    public function test_active_staff_can_checkout_a_takeaway_cart_through_order_service(): void
    {
        $staff = $this->staff();
        $variant = $this->variant();
        $this->recipe($variant);

        $this->actingAs($staff, 'web')->post(route('staff.pos.add'), ['variant_id' => $variant->id])
            ->assertRedirect(route('staff.home'));
        $this->get(route('staff.home'))->assertOk();
        $this->post(route('staff.pos.checkout'), $this->checkoutPayload([
            'order_type' => 'takeaway',
            'user_id' => 999,
            'status' => 'completed',
            'order_code' => 'FORGED',
            'subtotal' => '999.99',
            'total_amount' => '999.99',
        ]))->assertRedirect(route('staff.orders.show', 1))->assertSessionHas('success')->assertSessionMissing('pos_cart');

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
            'status' => 'preparing',
        ]);
        $this->assertDatabaseHas('payments', ['order_id' => $order->id, 'status' => 'paid', 'amount' => '35.50']);
    }

    public function test_dine_in_checkout_uses_the_order_service_table_workflow(): void
    {
        $staff = $this->staff();
        $variant = $this->variant();
        $this->recipe($variant);
        $table = $this->table();

        $this->actingAs($staff, 'web')->withSession(['pos_cart' => [$variant->id => 2]])->get(route('staff.home'))->assertOk();
        $this->post(route('staff.pos.checkout'), $this->checkoutPayload(['order_type' => 'dine_in', 'table_id' => $table->id]))
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
        $this->actingAs($staff, 'web')->withSession(['pos_cart' => [$variant->id => 1]])->get(route('staff.home'))->assertOk();
        $this->postJson(route('staff.pos.checkout'), $this->checkoutPayload(['order_type' => 'dine_in', 'table_id' => $occupiedTable->id]))
            ->assertUnprocessable()->assertJsonValidationErrors('table_id')->assertSessionHas('pos_cart');
        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('order_items', 0);

        $variant->forceFill(['status' => 'unavailable'])->save();
        $this->postJson(route('staff.pos.checkout'), $this->checkoutPayload())
            ->assertUnprocessable()->assertJsonValidationErrors('cart')->assertSessionHas('pos_cart');
        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('order_items', 0);
    }

    public function test_admin_can_operate_pos_while_guest_and_inactive_staff_cannot(): void
    {
        $variant = $this->variant();
        $this->get(route('staff.home'))->assertRedirect(route('login'));
        $this->post(route('staff.pos.add'), ['variant_id' => $variant->id])->assertRedirect(route('login'));

        $admin = $this->staff('active', 'admin');
        $this->actingAs($admin, 'web')->get(route('staff.home'))->assertOk();
        $this->post(route('staff.pos.add'), ['variant_id' => $variant->id])->assertRedirect(route('staff.home'));

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

    public function test_admin_prepaid_bank_checkout_preserves_toppings_notes_and_retries_without_duplicates(): void
    {
        $actor = $this->staff('active', 'admin');
        $variant = $this->variant();
        $topping = Topping::forceCreate(['name' => 'Cream', 'price' => '5.10', 'status' => 'available']);
        $ingredient = Ingredient::forceCreate(['name' => 'Coffee', 'unit' => 'g', 'current_stock' => '10.000', 'status' => 'active']);
        ProductRecipe::forceCreate(['variant_id' => $variant->id, 'ingredient_id' => $ingredient->id, 'quantity' => '1.500']);
        $this->actingAs($actor)->post(route('staff.pos.add'), ['variant_id' => $variant->id,
            'quantity' => 2, 'toppings' => [$topping->id => 3], 'note' => 'Less ice'])->assertSessionHasNoErrors();
        $this->get(route('staff.home'))->assertOk()->assertSee('86,30');
        $payload = $this->checkoutPayload(['payment_method' => 'bank_transfer', 'transaction_code' => ' BANK-1 ']);
        $this->post(route('staff.pos.checkout'), $payload)->assertRedirect(route('staff.orders.show', 1))->assertSessionHasNoErrors();
        $this->post(route('staff.pos.checkout'), $payload)->assertRedirect(route('staff.orders.show', 1))->assertSessionHasNoErrors();

        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseCount('payments', 1);
        $this->assertDatabaseCount('inventory_transactions', 1);
        $this->assertDatabaseHas('order_items', ['note' => 'Less ice', 'quantity' => 2, 'status' => 'preparing', 'subtotal' => '86.30']);
        $this->assertDatabaseHas('order_item_toppings', ['topping_name' => 'Cream', 'price' => '5.10', 'quantity' => 3]);
        $this->assertSame('86.30', Payment::sole()->amount);
        $this->assertSame($actor->id, Payment::sole()->created_by);
        $this->assertSame('BANK-1', Payment::sole()->transaction_code);
        $this->assertSame('7.000', $ingredient->fresh()->current_stock);
        $this->assertSame($actor->id, InventoryTransaction::sole()->created_by);
        $this->assertSame('pending', Order::sole()->status);
    }

    public function test_pos_does_not_create_order_without_receipt_confirmation_or_when_price_changed(): void
    {
        $actor = $this->staff();
        $variant = $this->variant();
        $this->actingAs($actor)->post(route('staff.pos.add'), ['variant_id' => $variant->id]);
        $this->get(route('staff.home'))->assertOk();
        $this->postJson(route('staff.pos.checkout'), $this->checkoutPayload(['received_confirmation' => null]))
            ->assertUnprocessable()->assertJsonValidationErrors('received_confirmation');
        $variant->forceFill(['price' => '40.00'])->saveOrFail();
        $this->postJson(route('staff.pos.checkout'), $this->checkoutPayload())
            ->assertUnprocessable()->assertJsonValidationErrors('cart')->assertSessionHas('pos_cart');
        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('payments', 0);
        $this->assertDatabaseCount('inventory_transactions', 0);
    }

    public function test_pos_stock_failure_rolls_back_order_paid_and_ledger_and_keeps_retry_key(): void
    {
        $actor = $this->staff();
        $variant = $this->variant();
        $ingredient = Ingredient::forceCreate(['name' => 'Coffee', 'unit' => 'g', 'current_stock' => '1.000', 'status' => 'active']);
        ProductRecipe::forceCreate(['variant_id' => $variant->id, 'ingredient_id' => $ingredient->id, 'quantity' => '2.000']);
        $this->actingAs($actor)->post(route('staff.pos.add'), ['variant_id' => $variant->id]);
        $this->get(route('staff.home'))->assertOk();
        $payload = $this->checkoutPayload();
        $this->postJson(route('staff.pos.checkout'), $payload)->assertUnprocessable()->assertSessionHas('pos_cart');
        $this->assertSame($payload['checkout_key'], session('pos_checkout_attempt.key'));
        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('order_items', 0);
        $this->assertDatabaseCount('payments', 0);
        $this->assertDatabaseCount('inventory_transactions', 0);
        $this->assertSame('1.000', $ingredient->fresh()->current_stock);
    }

    public function test_cash_tender_is_recorded_and_replay_cannot_change_it(): void
    {
        $actor = $this->staff();
        $variant = $this->variant();
        $this->recipe($variant);
        $this->actingAs($actor)->post(route('staff.pos.add'), ['variant_id' => $variant->id]);
        $this->get(route('staff.home'))->assertOk();
        $payload = $this->checkoutPayload(['received_amount' => '50.00']);
        $this->post(route('staff.pos.checkout'), $payload)->assertSessionHasNoErrors();
        $this->assertSame('14.50', Payment::sole()->change_amount);
        $this->post(route('staff.pos.checkout'), $payload)->assertSessionHasNoErrors();
        $this->postJson(route('staff.pos.checkout'), array_replace($payload, ['received_amount' => '60.00']))
            ->assertUnprocessable()->assertJsonValidationErrors('cart');
        $this->assertDatabaseCount('payments', 1);
        $this->assertDatabaseCount('inventory_transactions', 1);
    }

    public function test_checkout_without_cash_tender_keeps_the_previous_release_payload_hash(): void
    {
        $actor = $this->staff();
        $variant = $this->variant();
        $this->recipe($variant);
        $this->actingAs($actor)->post(route('staff.pos.add'), ['variant_id' => $variant->id]);
        $this->get(route('staff.home'))->assertOk();
        $attempt = session('pos_checkout_attempt');
        $payload = $this->checkoutPayload();
        $legacyHash = hash('sha256', json_encode([$attempt['draft'], $attempt['quote_hash'], 'takeaway', null, null, 'cash', ''], JSON_THROW_ON_ERROR));
        $this->post(route('staff.pos.checkout'), $payload)->assertSessionHasNoErrors();
        $this->assertSame($legacyHash, Order::sole()->pos_checkout_payload_hash);
        $this->post(route('staff.pos.checkout'), $payload)->assertSessionHasNoErrors();
        $this->assertDatabaseCount('orders', 1);
    }

    public function test_variant_without_recipe_cannot_create_a_paid_pos_order(): void
    {
        $actor = $this->staff();
        $variant = $this->variant();
        $this->actingAs($actor)->post(route('staff.pos.add'), ['variant_id' => $variant->id]);
        $this->get(route('staff.home'))->assertOk();
        $this->postJson(route('staff.pos.checkout'), $this->checkoutPayload())
            ->assertUnprocessable()->assertJsonValidationErrors('ingredient')->assertSessionHas('pos_cart');
        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('payments', 0);
        $this->assertDatabaseCount('inventory_transactions', 0);
    }

    public function test_pos_acceptance_exact_cash_and_insufficient_cash_are_atomic(): void
    {
        $actor = $this->staff();
        $variant = $this->variant();
        $this->recipe($variant);
        $this->actingAs($actor)->post(route('staff.pos.add'), ['variant_id' => $variant->id]);
        $this->get(route('staff.home'))->assertOk();
        $this->postJson(route('staff.pos.checkout'), $this->checkoutPayload(['received_amount' => '10.00']))
            ->assertUnprocessable()->assertSessionHas('pos_cart');
        foreach (['orders', 'order_items', 'payments', 'inventory_transactions'] as $table) {
            $this->assertDatabaseCount($table, 0);
        }
        $this->post(route('staff.pos.checkout'), $this->checkoutPayload(['received_amount' => '35.50']))
            ->assertSessionHasNoErrors()->assertSessionMissing('pos_cart');
        $this->assertSame('35.50', Payment::sole()->received_amount);
        $this->assertSame('0.00', Payment::sole()->change_amount);
        $this->assertSame($actor->id, Payment::sole()->created_by);
    }

    public function test_pos_acceptance_bank_reference_cannot_pay_a_second_cart(): void
    {
        $actor = $this->staff();
        $variant = $this->variant();
        $this->recipe($variant);
        $this->actingAs($actor)->post(route('staff.pos.add'), ['variant_id' => $variant->id]);
        $this->get(route('staff.home'))->assertOk();
        $this->post(route('staff.pos.checkout'), $this->checkoutPayload([
            'payment_method' => 'bank_transfer', 'transaction_code' => 'QA-POS-REFERENCE',
        ]))->assertSessionHasNoErrors();
        $stock = Ingredient::sole()->current_stock;
        $this->post(route('staff.pos.add'), ['variant_id' => $variant->id]);
        $this->get(route('staff.home'))->assertOk();
        $this->postJson(route('staff.pos.checkout'), $this->checkoutPayload([
            'payment_method' => 'bank_transfer', 'transaction_code' => 'QA-POS-REFERENCE',
        ]))->assertUnprocessable()->assertSessionHas('pos_cart');
        foreach (['orders', 'order_items', 'payments', 'inventory_transactions'] as $table) {
            $this->assertDatabaseCount($table, 1);
        }
        $this->assertSame($stock, Ingredient::sole()->current_stock);
    }

    public function test_pos_acceptance_table_becomes_unavailable_after_review(): void
    {
        $actor = $this->staff();
        $variant = $this->variant();
        $this->recipe($variant);
        $table = $this->table();
        $this->actingAs($actor)->post(route('staff.pos.add'), ['variant_id' => $variant->id]);
        $this->get(route('staff.home'))->assertOk();
        foreach (['occupied', 'maintenance'] as $status) {
            $table->forceFill(['status' => $status])->saveOrFail();
            $this->postJson(route('staff.pos.checkout'), $this->checkoutPayload([
                'order_type' => 'dine_in', 'table_id' => $table->id,
            ]))->assertUnprocessable()->assertSessionHas('pos_cart');
            $this->assertSame($status, $table->fresh()->status);
            foreach (['orders', 'order_items', 'payments', 'inventory_transactions'] as $name) {
                $this->assertDatabaseCount($name, 0);
            }
        }
    }

    public function test_pos_acceptance_refresh_and_paid_mutation_endpoints_keep_receipt_immutable(): void
    {
        $actor = $this->staff();
        $variant = $this->variant();
        $this->recipe($variant);
        $topping = Topping::forceCreate(['name' => 'QA Cream', 'price' => '5.10', 'status' => 'available']);
        $this->actingAs($actor)->post(route('staff.pos.add'), [
            'variant_id' => $variant->id, 'quantity' => 2, 'toppings' => [$topping->id => 1], 'note' => 'QA note',
        ]);
        $this->get(route('staff.home'))->assertOk();
        $this->post(route('staff.pos.checkout'), $this->checkoutPayload())->assertSessionHasNoErrors();
        $order = Order::sole();
        $item = $order->orderItems()->sole();
        $row = $item->orderItemToppings()->sole();
        $this->get(route('staff.orders.show', $order))->assertOk();
        $this->get(route('staff.orders.show', $order))->assertOk();
        $this->postJson(route('staff.orders.items.store', $order), ['variant_id' => $variant->id, 'quantity' => 1])->assertUnprocessable();
        $this->patchJson(route('staff.orders.items.update', [$order, $item]), ['quantity' => 5])->assertUnprocessable();
        $this->deleteJson(route('staff.orders.items.destroy', [$order, $item]))->assertUnprocessable();
        $this->postJson(route('staff.orders.items.toppings.store', [$order, $item]), ['topping_id' => $topping->id, 'quantity' => 1])->assertUnprocessable();
        $this->patchJson(route('staff.orders.items.toppings.update', [$order, $item, $row]), ['quantity' => 3])->assertUnprocessable();
        $this->deleteJson(route('staff.orders.items.toppings.destroy', [$order, $item, $row]))->assertUnprocessable();
        $this->patchJson(route('staff.orders.cancel', $order))->assertUnprocessable();
        $this->assertSame('76.10', $order->fresh()->total_amount);
        $this->assertSame(2, $item->fresh()->quantity);
        $this->assertSame(1, $row->fresh()->quantity);
        foreach (['orders', 'order_items', 'order_item_toppings', 'payments', 'inventory_transactions'] as $table) {
            $this->assertDatabaseCount($table, 1);
        }
    }
}
