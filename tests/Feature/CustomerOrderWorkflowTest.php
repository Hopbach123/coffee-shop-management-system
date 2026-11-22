<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Customer;
use App\Models\CustomerOrderRequest;
use App\Models\CustomerOrderRequestItem;
use App\Models\Ingredient;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductRecipe;
use App\Models\ProductVariant;
use App\Models\Promotion;
use App\Models\Topping;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CustomerOrderWorkflowTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame('sqlite', DB::connection()->getDriverName());
        $this->assertSame(':memory:', DB::connection()->getDatabaseName());
        foreach (['0001_01_01_000000_create_users_table.php', '2026_08_14_000001_create_customers_table.php',
            '2026_08_14_000003_create_categories_table.php', '2026_08_14_000004_create_toppings_table.php',
            '2026_08_14_000005_create_ingredients_table.php', '2026_08_14_000007_create_products_table.php',
            '2026_08_14_000008_create_promotions_table.php'] as $migration) {
            (require database_path('migrations/'.$migration))->up();
        }
        Schema::create('product_variants', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_id')->constrained('products');
            $table->string('name', 50);
            $table->decimal('price', 12, 2);
            $table->string('status');
            $table->softDeletes();
            $table->timestamps();
        });
        Schema::create('orders', function (Blueprint $table): void {
            $table->id();
            $table->string('order_code')->unique();
            $table->foreignId('user_id')->constrained('users');
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->unsignedBigInteger('table_id')->nullable();
            $table->unsignedBigInteger('reservation_id')->nullable();
            $table->string('order_type');
            $table->string('status');
            $table->text('note')->nullable();
            foreach (['subtotal', 'discount_amount', 'tax_amount', 'total_amount'] as $column) {
                $table->decimal($column, 15, 2)->default(0);
            }
            $table->dateTime('ordered_at');
            $table->dateTime('completed_at')->nullable();
            $table->timestamps();
        });
        Schema::create('order_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id')->constrained('orders');
            $table->foreignId('product_id')->constrained('products');
            $table->foreignId('variant_id')->constrained('product_variants');
            $table->string('product_name');
            $table->string('variant_name', 50)->nullable();
            $table->integer('quantity');
            $table->decimal('unit_price', 12, 2);
            $table->decimal('topping_amount', 12, 2)->default(0);
            $table->decimal('subtotal', 15, 2);
            $table->string('note', 500)->nullable();
            $table->string('status');
            $table->timestamps();
        });
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
        Schema::create('payments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id')->constrained('orders');
            $table->string('payment_code')->unique();
            $table->string('payment_method');
            $table->decimal('amount', 15, 2);
            $table->string('transaction_code', 100)->nullable();
            $table->string('status');
            $table->dateTime('paid_at')->nullable();
            $table->foreignId('created_by')->constrained('users');
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
            foreach (['quantity', 'before_quantity', 'after_quantity'] as $column) {
                $table->decimal($column, 12, 3);
            }
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->text('note')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });
        (require database_path('migrations/2026_10_27_000002_add_delivery_fee_to_orders_table.php'))->up();
        (require database_path('migrations/2026_10_27_000003_create_customer_order_requests_tables.php'))->up();
        (require database_path('migrations/2026_11_22_000001_add_promotion_snapshot_to_customer_order_requests.php'))->up();
        (require database_path('migrations/2026_11_18_000001_add_finance_receipt_keys_to_payments_table.php'))->up();
        $this->withoutVite();
    }

    private function catalog(string $stock = '20.000'): array
    {
        $category = Category::forceCreate(['name' => 'Coffee', 'status' => 'active']);
        $product = Product::forceCreate(['category_id' => $category->id, 'name' => 'Latte', 'slug' => 'latte', 'status' => 'available']);
        $variant = ProductVariant::forceCreate(['product_id' => $product->id, 'name' => 'Large', 'price' => '30000.25', 'status' => 'available']);
        $topping = Topping::forceCreate(['name' => 'Cream', 'price' => '5000.10', 'status' => 'available']);
        $ingredient = Ingredient::forceCreate(['name' => 'Coffee beans', 'unit' => 'g', 'current_stock' => $stock, 'status' => 'active']);
        ProductRecipe::forceCreate(['variant_id' => $variant->id, 'ingredient_id' => $ingredient->id, 'quantity' => '2.500']);

        return [$variant, $topping, $ingredient];
    }

    private function actor(string $status = 'active', string $role = 'staff'): User
    {
        return User::forceCreate(['name' => 'Cashier', 'email' => uniqid().'@example.test', 'password' => 'test-password', 'role' => $role, 'status' => $status]);
    }

    private function startGuestRequest(string $option = 'cod', string $stock = '20.000'): array
    {
        [$variant, $topping, $ingredient] = $this->catalog($stock);
        $this->post(route('cart.items.store'), ['variant_id' => $variant->id, 'quantity' => 2, 'toppings' => [$topping->id => 1], 'note' => 'Less ice'])->assertSessionHasNoErrors();
        $this->get(route('customer-orders.create'))->assertOk()->assertSee('85.000,60');
        $payload = ['customer_name' => 'Guest A', 'customer_phone' => '0900000001', 'customer_address' => '123 Test Street',
            'note' => 'Call before delivery', 'payment_option' => $option, 'submission_key' => session('customer_order_attempt.key'),
            'user_id' => 999, 'customer_id' => 999, 'status' => 'accepted', 'quoted_total' => '1.00', 'delivery_fee' => '0.00'];

        return [$payload, $variant, $ingredient];
    }

    private function submit(string $option = 'cod', string $stock = '20.000'): array
    {
        [$payload, $variant, $ingredient] = $this->startGuestRequest($option, $stock);
        $this->post(route('customer-orders.store'), $payload)->assertSessionHasNoErrors();

        return [CustomerOrderRequest::sole(), $variant, $ingredient, $payload];
    }

    public function test_guest_submission_snapshots_contact_fee_and_toppings_without_order_or_payment(): void
    {
        $master = Customer::forceCreate(['name' => 'Existing customer', 'phone' => '0900000001', 'status' => 'active']);
        [$record, , , $payload] = $this->submit();
        $this->assertGuest();
        $this->assertSame('pending', $record->status);
        $this->assertSame('65000.60', $record->quoted_subtotal);
        $this->assertSame('20000.00', $record->delivery_fee);
        $this->assertSame('85000.60', $record->quoted_total);
        $this->assertNull($record->order_id);
        $this->assertSame('Existing customer', $master->fresh()->name);
        $this->assertDatabaseCount('customers', 1);
        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('payments', 0);
        $this->assertDatabaseCount('inventory_transactions', 0);
        $this->assertDatabaseHas('customer_order_request_item_toppings', ['topping_name' => 'Cream', 'quantity' => 1, 'price' => '5000.10']);
        $this->post(route('customer-orders.store'), $payload)->assertRedirect(route('customer-orders.show', $record->reference));
        $this->assertDatabaseCount('customer_order_requests', 1);
        $this->get(route('customer-orders.show', $record->reference))->assertOk()->assertSee('Chưa thanh toán')->assertDontSee($record->access_token_hash);
    }

    public function test_customer_can_apply_one_eligible_promotion_and_staff_rechecks_snapshot_on_acceptance(): void
    {
        Promotion::forceCreate([
            'code' => 'CAFE10', 'name' => 'Ten percent', 'discount_type' => 'percentage',
            'discount_value' => '10.00', 'max_discount' => '5000.00', 'minimum_order' => '50000.00',
            'start_at' => now()->subHour(), 'end_at' => now()->addHour(), 'usage_limit' => 3,
            'used_count' => 0, 'status' => 'active',
        ]);
        [$payload] = $this->startGuestRequest();
        $this->post(route('customer-orders.promotion.apply'), ['promotion_code' => 'cafe10'])->assertRedirect(route('customer-orders.create'));
        $this->get(route('customer-orders.create'))->assertOk()->assertSee('CAFE10');
        $payload['submission_key'] = session('customer_order_attempt.key');
        $this->post(route('customer-orders.store'), $payload)->assertSessionHasNoErrors();
        $record = CustomerOrderRequest::sole();
        $this->assertSame('CAFE10', $record->promotion_code);
        $this->assertSame('5000.00', $record->promotion_discount);
        $this->assertSame('80000.60', $record->quoted_total);

        $this->actingAs($this->actor())->post(route('staff.customer-orders.accept', $record))->assertSessionHasNoErrors();
        $order = Order::sole();
        $this->assertSame('80000.60', $order->total_amount);
        $this->assertDatabaseHas('order_promotions', ['order_id' => $order->id, 'promotion_code' => 'CAFE10', 'discount_amount' => '5000.00']);
    }

    public function test_guest_can_view_and_save_an_active_public_promotion_without_consuming_usage(): void
    {
        $promotion = Promotion::forceCreate([
            'code' => 'PUBLIC10', 'name' => 'Public ten', 'discount_type' => 'fixed',
            'discount_value' => '10000.00', 'minimum_order' => '0.00', 'start_at' => now()->subHour(),
            'end_at' => now()->addHour(), 'usage_limit' => 2, 'used_count' => 0, 'status' => 'active',
        ]);
        Promotion::forceCreate([
            'code' => 'EXPIRED', 'name' => 'Expired', 'discount_type' => 'fixed',
            'discount_value' => '10000.00', 'minimum_order' => '0.00', 'start_at' => now()->subDays(2),
            'end_at' => now()->subHour(), 'usage_limit' => 2, 'used_count' => 0, 'status' => 'active',
        ]);

        $this->get(route('promotions.index'))->assertOk()->assertSee('PUBLIC10')->assertDontSee('EXPIRED');
        $this->post(route('promotions.save', $promotion))->assertSessionHas('public_saved_promotion_codes', ['PUBLIC10']);
        $this->assertSame(0, $promotion->fresh()->used_count);
    }

    public function test_acceptance_rejects_when_applied_promotion_has_changed(): void
    {
        $promotion = Promotion::forceCreate([
            'code' => 'CHANGE10', 'name' => 'Change test', 'discount_type' => 'percentage',
            'discount_value' => '10.00', 'minimum_order' => '0.00', 'start_at' => now()->subHour(),
            'end_at' => now()->addHour(), 'status' => 'active', 'used_count' => 0,
        ]);
        [$payload] = $this->startGuestRequest();
        $this->post(route('customer-orders.promotion.apply'), ['promotion_code' => 'CHANGE10']);
        $this->get(route('customer-orders.create'))->assertOk();
        $payload['submission_key'] = session('customer_order_attempt.key');
        $this->post(route('customer-orders.store'), $payload)->assertSessionHasNoErrors();
        $record = CustomerOrderRequest::sole();
        $promotion->forceFill(['status' => 'inactive'])->saveOrFail();

        $this->actingAs($this->actor())->postJson(route('staff.customer-orders.accept', $record))
            ->assertUnprocessable()->assertJsonValidationErrors('request');
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_tracking_requires_the_original_session_and_expires_after_seven_days(): void
    {
        [$record] = $this->submit();
        $token = session('customer_order_access.'.$record->reference);
        $this->withSession(['customer_order_access' => []])->get(route('customer-orders.show', $record->reference))->assertNotFound();
        $this->get(route('customer-orders.show', $record->reference).'?phone=0900000001')->assertNotFound();
        $this->withSession(['customer_order_access' => [$record->reference => $token]])->get(route('customer-orders.show', $record->reference))->assertOk();
        $this->travel(8)->days();
        $this->get(route('customer-orders.show', $record->reference))->assertNotFound();
    }

    public function test_web_bank_transfer_is_accepted_once_and_requires_actual_payment_before_preparing(): void
    {
        [$record, , $ingredient] = $this->submit('bank_transfer_prepaid');
        $actor = $this->actor('active', 'admin');
        $this->actingAs($actor)->post(route('staff.customer-orders.accept', $record))->assertSessionHasNoErrors();
        $this->post(route('staff.customer-orders.accept', $record))->assertSessionHasNoErrors();
        $order = Order::sole();
        $this->assertSame($actor->id, $order->user_id);
        $this->assertNull($order->customer_id);
        $this->assertNull($order->table_id);
        $this->assertSame('takeaway', $order->order_type);
        $this->assertSame('85000.60', $order->total_amount);
        $this->assertSame('20000.00', $order->delivery_fee);
        $item = $order->orderItems()->sole();
        $this->patchJson(route('staff.orders.items.status.update', [$order, $item]), ['status' => 'preparing'])->assertUnprocessable()->assertJsonValidationErrors('payment');
        $this->post(route('staff.orders.payments.store', $order), ['payment_method' => 'bank_transfer', 'received_confirmation' => '1'])->assertSessionHasNoErrors();
        $this->assertSame('preparing', $item->fresh()->status);
        $this->assertSame('pending', $order->fresh()->status);
        $this->assertSame('15.000', $ingredient->fresh()->current_stock);
        $this->assertSame('85000.60', Payment::sole()->amount);
        $this->assertSame($actor->id, Payment::sole()->created_by);
        $this->patch(route('staff.orders.items.status.update', [$order, $item]), ['status' => 'ready'])->assertSessionHasNoErrors();
        $this->post(route('staff.customer-orders.dispatch', $record))->assertSessionHasNoErrors();
        $this->post(route('staff.customer-orders.deliver', $record), ['delivery_confirmation' => '1'])->assertSessionHasNoErrors();
        $this->assertSame('completed', $order->fresh()->status);
        $this->assertNotNull($record->fresh()->delivered_at);
        $this->assertDatabaseCount('payments', 1);
    }

    public function test_cod_prepares_unpaid_and_records_paid_only_when_customer_received_delivery(): void
    {
        [$record, , $ingredient] = $this->submit();
        $actor = $this->actor();
        $this->actingAs($actor)->post(route('staff.customer-orders.accept', $record))->assertSessionHasNoErrors();
        $this->post(route('staff.customer-orders.prepare', $record))->assertSessionHasNoErrors();
        $order = Order::sole();
        $item = $order->orderItems()->sole();
        $this->assertSame('preparing', $item->status);
        $this->assertDatabaseCount('payments', 0);
        $this->patch(route('staff.orders.items.status.update', [$order, $item]), ['status' => 'ready'])->assertSessionHasNoErrors();
        $this->patchJson(route('staff.orders.items.status.update', [$order, $item]), ['status' => 'served'])->assertUnprocessable();
        $this->post(route('staff.customer-orders.dispatch', $record))->assertSessionHasNoErrors();
        $this->assertNotNull($record->fresh()->dispatched_at);
        $this->assertDatabaseCount('payments', 0);
        $this->postJson(route('staff.customer-orders.deliver', $record), ['delivery_confirmation' => '1'])->assertUnprocessable();
        $payload = ['delivery_confirmation' => '1', 'received_confirmation' => '1', 'payment_method' => 'cash'];
        $this->post(route('staff.customer-orders.deliver', $record), $payload)->assertSessionHasNoErrors();
        $this->post(route('staff.customer-orders.deliver', $record), $payload)->assertSessionHasNoErrors();
        $this->assertNotNull($record->fresh()->delivered_at);
        $this->assertSame('served', $item->fresh()->status);
        $this->assertSame('completed', $order->fresh()->status);
        $this->assertDatabaseCount('payments', 1);
        $this->assertDatabaseCount('inventory_transactions', 1);
        $this->assertSame('15.000', $ingredient->fresh()->current_stock);
    }

    public function test_catalog_price_change_blocks_submission_and_acceptance_without_partial_order(): void
    {
        [$payload, $variant] = $this->startGuestRequest();
        $variant->forceFill(['price' => '31000.00'])->saveOrFail();
        $this->postJson(route('customer-orders.store'), $payload)->assertUnprocessable()->assertJsonValidationErrors('cart')->assertSessionHas('customer_order_cart');
        $this->assertDatabaseCount('customer_order_requests', 0);
        $this->get(route('customer-orders.create'))->assertOk();
        $payload['submission_key'] = session('customer_order_attempt.key');
        $this->post(route('customer-orders.store'), $payload)->assertSessionHasNoErrors();
        $record = CustomerOrderRequest::sole();
        $variant->forceFill(['status' => 'unavailable'])->saveOrFail();
        $this->actingAs($this->actor())->postJson(route('staff.customer-orders.accept', $record))->assertUnprocessable();
        $this->assertDatabaseCount('orders', 0);
        $this->assertNull($record->fresh()->order_id);
    }

    public function test_guest_and_inactive_actors_cannot_process_requests(): void
    {
        [$record] = $this->submit();
        $this->post(route('staff.customer-orders.accept', $record))->assertRedirect(route('login'));
        $this->actingAs($this->actor('inactive'))->post(route('staff.customer-orders.accept', $record))->assertForbidden();
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_insufficient_stock_during_bank_confirmation_rolls_back_paid_and_consumption(): void
    {
        [$record, , $ingredient] = $this->submit('bank_transfer_prepaid', '1.000');
        $this->actingAs($this->actor())->post(route('staff.customer-orders.accept', $record))->assertSessionHasNoErrors();
        $order = Order::sole();
        $this->postJson(route('staff.orders.payments.store', $order), ['payment_method' => 'bank_transfer', 'received_confirmation' => '1'])->assertUnprocessable();
        $this->assertDatabaseCount('payments', 0);
        $this->assertDatabaseCount('inventory_transactions', 0);
        $this->assertSame('pending', $order->orderItems()->sole()->status);
        $this->assertSame('1.000', $ingredient->fresh()->current_stock);
    }

    public function test_failed_request_item_storage_rolls_back_header_and_keeps_cart(): void
    {
        [$payload] = $this->startGuestRequest();
        CustomerOrderRequestItem::creating(fn () => throw new QueryException('sqlite', 'test', [], new \RuntimeException('injected')));
        try {
            $this->post(route('customer-orders.store'), $payload)->assertSessionHasErrors('cart')->assertSessionHas('customer_order_cart');
            $this->assertDatabaseCount('customer_order_requests', 0);
            $this->assertDatabaseCount('customer_order_request_items', 0);
        } finally {
            CustomerOrderRequestItem::flushEventListeners();
        }
    }

    public function test_web_configuration_is_locked_and_cod_cannot_be_paid_through_the_legacy_endpoint(): void
    {
        [$record, $variant] = $this->submit();
        $this->actingAs($this->actor())->post(route('staff.customer-orders.accept', $record))->assertSessionHasNoErrors();
        $order = Order::sole();
        $item = $order->orderItems()->sole();
        $this->postJson(route('staff.orders.items.store', $order), ['variant_id' => $variant->id, 'quantity' => 1])->assertUnprocessable()->assertJsonValidationErrors('order');
        $this->patchJson(route('staff.orders.items.update', [$order, $item]), ['quantity' => 3])->assertUnprocessable()->assertJsonValidationErrors('order');
        $this->deleteJson(route('staff.orders.items.destroy', [$order, $item]))->assertUnprocessable()->assertJsonValidationErrors('order');
        $this->postJson(route('staff.orders.payments.store', $order), ['payment_method' => 'cash', 'received_confirmation' => '1'])->assertUnprocessable()->assertJsonValidationErrors('payment');
        $this->assertSame(2, $item->fresh()->quantity);
        $this->assertSame('85000.60', $order->fresh()->total_amount);
        $this->assertDatabaseCount('payments', 0);
    }

    public function test_invalid_contact_is_rejected_and_does_not_create_a_request(): void
    {
        [$payload] = $this->startGuestRequest();
        $this->postJson(route('customer-orders.store'), array_replace($payload, [
            'customer_name' => '', 'customer_phone' => 'not-a-phone', 'customer_address' => '',
        ]))->assertUnprocessable()->assertJsonValidationErrors(['customer_name', 'customer_phone', 'customer_address']);
        $this->assertDatabaseCount('customer_order_requests', 0);
    }

    public function test_cod_delivery_storage_failure_rolls_back_receipt_and_served_states(): void
    {
        [$record] = $this->submit();
        $this->actingAs($this->actor())->post(route('staff.customer-orders.accept', $record));
        $this->post(route('staff.customer-orders.prepare', $record));
        $order = Order::sole();
        $item = $order->orderItems()->sole();
        $this->patch(route('staff.orders.items.status.update', [$order, $item]), ['status' => 'ready']);
        $this->post(route('staff.customer-orders.dispatch', $record));
        Payment::creating(fn () => throw new QueryException('sqlite', 'test', [], new \RuntimeException('injected')));
        try {
            $this->post(route('staff.customer-orders.deliver', $record), [
                'delivery_confirmation' => '1', 'received_confirmation' => '1', 'payment_method' => 'cash',
            ])->assertSessionHasErrors('request');
            $this->assertNull($record->fresh()->delivered_at);
            $this->assertNotNull($record->fresh()->dispatched_at);
            $this->assertSame('ready', $item->fresh()->status);
            $this->assertSame('pending', $order->fresh()->status);
            $this->assertDatabaseCount('payments', 0);
            $this->assertDatabaseCount('inventory_transactions', 1);
        } finally {
            Payment::flushEventListeners();
        }
    }

    public function test_submission_rate_limit_blocks_the_sixth_request_in_one_session(): void
    {
        session()->start();
        $sessionId = session()->getId();
        $this->withCredentials()->withCookie(config('session.cookie'), $sessionId);
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->postJson(route('customer-orders.store'), [])->assertUnprocessable();
            $this->assertSame($sessionId, session()->getId());
        }
        $this->postJson(route('customer-orders.store'), [])->assertStatus(429);
        $this->assertDatabaseCount('customer_order_requests', 0);
    }
}
