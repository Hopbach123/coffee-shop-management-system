<?php

namespace Tests\Feature;

use App\Http\Controllers\Public\CartController;
use App\Http\Controllers\Public\OrderTrackingController;
use App\Models\Category;
use App\Models\Ingredient;
use App\Models\OnlineOrderRequest;
use App\Models\OnlineOrderRequestItem;
use App\Models\OnlineOrderRequestItemTopping;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductRecipe;
use App\Models\ProductVariant;
use App\Models\Topping;
use App\Models\User;
use App\Services\OrderService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class OnlineOrderWorkflowTest extends TestCase
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

        Schema::create('customers', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('phone', 20)->nullable();
            $table->timestamps();
        });

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

        Schema::create('online_order_requests', function (Blueprint $table): void {
            $table->id();
            $table->string('request_code', 50)->unique();
            $table->string('purchase_type', 20); // online, scheduled
            $table->foreignId('customer_id')->nullable()->constrained('customers');
            $table->string('contact_name');
            $table->string('contact_phone', 20);
            $table->dateTime('pickup_at')->nullable();
            $table->string('note', 500)->nullable();
            $table->string('status', 20)->default('pending'); // pending, accepted, rejected, cancelled
            $table->string('payment_preference', 20); // cash, bank_transfer
            $table->string('payment_timing', 20)->default('on_receipt'); // on_receipt, prepaid
            $table->foreignId('accepted_by')->nullable()->constrained('users');
            $table->dateTime('accepted_at')->nullable();
            $table->string('rejection_reason', 500)->nullable();
            $table->string('submission_key', 64)->unique();
            $table->char('tracking_token_hash', 64)->unique();
            $table->timestamps();
        });

        Schema::create('online_order_request_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('request_id')->constrained('online_order_requests')->cascadeOnDelete();
            $table->foreignId('variant_id')->constrained('product_variants');
            $table->integer('quantity');
            $table->string('note', 500)->nullable();
            $table->string('product_name');
            $table->string('variant_name');
            $table->decimal('unit_price', 12, 2);
            $table->timestamps();
        });

        Schema::create('online_order_request_item_toppings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('request_item_id')->constrained('online_order_request_items')->cascadeOnDelete();
            $table->foreignId('topping_id')->constrained('toppings');
            $table->integer('quantity')->default(1);
            $table->string('topping_name');
            $table->decimal('price', 12, 2);
            $table->timestamps();
        });

        Schema::create('orders', function (Blueprint $table): void {
            $table->id();
            $table->string('order_code', 50)->unique();
            $table->string('purchase_type', 20)->default('counter'); // counter, online, scheduled
            $table->foreignId('online_request_id')->nullable()->constrained('online_order_requests');
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

        Schema::create('payments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->restrictOnDelete();
            $table->string('payment_code', 50)->unique();
            $table->string('payment_method', 20); // cash, bank_transfer
            $table->decimal('amount', 15, 2);
            $table->string('transaction_code', 100)->nullable();
            $table->string('status', 20)->default('pending'); // pending, paid, failed, refunded
            $table->dateTime('paid_at')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });

        Route::middleware('web')->group(function () {
            Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
            Route::post('/cart', [CartController::class, 'add'])->name('cart.add');
            Route::patch('/cart', [CartController::class, 'update'])->name('cart.update');
            Route::get('/checkout', [CartController::class, 'checkout'])->name('cart.checkout');
            Route::post('/checkout', [CartController::class, 'place'])->name('cart.place');
            Route::get('/checkout/confirmation', [CartController::class, 'confirmation'])->name('cart.confirmation');
            Route::get('/orders/track/{token}', [OrderTrackingController::class, 'show'])->name('orders.track');
        });
        Route::getRoutes()->refreshNameLookups();

        $this->withoutVite();
    }

    private function createStaff(string $role = 'staff'): User
    {
        return User::forceCreate([
            'name' => 'Staff Test',
            'email' => uniqid('staff-').'@example.test',
            'password' => Hash::make('password'),
            'role' => $role,
            'status' => 'active',
        ]);
    }

    private function createProductWithVariant(string $productName = 'Cà phê sữa đá', float $price = 35000): array
    {
        $category = Category::forceCreate(['name' => 'Cà phê', 'status' => 'active', 'display_order' => 1]);
        $product = Product::forceCreate(['category_id' => $category->id, 'name' => $productName, 'slug' => uniqid('sp-'), 'status' => 'available']);
        $variant = ProductVariant::forceCreate([
            'product_id' => $product->id,
            'name' => 'Size M',
            'price' => $price,
            'status' => 'available',
        ]);

        return [$product, $variant];
    }

    private function createTopping(string $name = 'Trân châu trắng', float $price = 10000): Topping
    {
        return Topping::forceCreate([
            'name' => $name,
            'price' => $price,
            'status' => 'available',
        ]);
    }

    /**
     * T01: Cart with different sizes/toppings and line signatures.
     */
    public function test_cart_supports_line_signatures_with_variants_toppings_and_notes(): void
    {
        [, $variant] = $this->createProductWithVariant();
        $topping1 = $this->createTopping('Trân châu', 10000);
        $topping2 = $this->createTopping('Thạch sương sáo', 8000);

        // Add Line 1: Variant + Topping 1 + ít đường
        $this->post(route('cart.add'), [
            'variant_id' => $variant->id,
            'quantity' => 1,
            'toppings' => [$topping1->id],
            'note' => 'Ít đường',
        ])->assertRedirect(route('pickup.cart.index'));

        // Add Line 2: Same variant + Topping 2 + không đá
        $this->post(route('cart.add'), [
            'variant_id' => $variant->id,
            'quantity' => 2,
            'toppings' => [$topping2->id],
            'note' => 'Không đá',
        ])->assertRedirect(route('pickup.cart.index'));

        $cart = session('customer_cart');
        $this->assertCount(2, $cart);

        // Verify total recalculation on checkout page: Line 1 = 35k + 10k = 45k. Line 2 = (35k + 8k) * 2 = 86k. Total = 131.000đ.
        $response = $this->get(route('cart.checkout'));
        $response->assertOk();
        $response->assertSee('131.000đ');
        $response->assertSee('Ít đường');
        $response->assertSee('Không đá');
    }

    /**
     * T02: Frontend price tampered, server re-calculates price.
     */
    public function test_tampered_frontend_price_is_recalculated_by_server(): void
    {
        [, $variant] = $this->createProductWithVariant('Bạc xỉu', 40000);

        // Attacker attempts to post unit_price = 100
        $this->post(route('cart.add'), [
            'variant_id' => $variant->id,
            'quantity' => 2,
            'unit_price' => 100,
        ])->assertRedirect(route('pickup.cart.index'));

        // Server forces DB price 40.000đ * 2 = 80.000đ
        $this->get(route('pickup.cart.index'))->assertOk()->assertSee('80.000đ');
    }

    /**
     * T03: Price change forces re-confirmation.
     */
    public function test_price_change_forces_reconfirmation(): void
    {
        [, $variant] = $this->createProductWithVariant('Cold Brew', 45000);

        $this->post(route('cart.add'), ['variant_id' => $variant->id, 'quantity' => 1]);
        $this->get(route('cart.checkout'))->assertOk();

        // Simulate admin changing price from 45000 to 50000
        $variant->update(['price' => 50000]);

        // Placing order without confirmation throws validation exception
        $response = $this->post(route('cart.place'), [
            'purchase_type' => 'online',
            'customer_name' => 'Khách A',
            'customer_phone' => '0901234567',
        ]);
        $response->assertSessionHasErrors('cart');

        // A refreshed checkout displays the exact updated quote.
        $this->get(route('cart.checkout'))->assertOk()->assertSee('50.000');
        // Confirming the refreshed quote allows order to proceed
        $response2 = $this->post(route('cart.place'), [
            'purchase_type' => 'online',
            'customer_name' => 'Khách A',
            'customer_phone' => '0901234567',
            'confirm_price_change' => '1',
        ]);
        $response2->assertRedirect();
        $this->assertDatabaseHas('online_order_requests', [
            'contact_name' => 'Khách A',
            'purchase_type' => 'online',
            'status' => 'pending',
        ]);
    }

    /**
     * T04: POS counter order creation sets purchase_type = 'counter'.
     */
    public function test_pos_counter_order_creation_sets_purchase_type_counter(): void
    {
        $staff = $this->createStaff();
        [, $variant] = $this->createProductWithVariant('Espresso', 30000);

        $service = app(OrderService::class);
        $order = $service->create([
            'user_id' => $staff->id,
            'order_type' => 'takeaway',
            'purchase_type' => 'counter',
            'ordered_at' => now(),
            'items' => [
                [
                    'variant_id' => $variant->id,
                    'quantity' => 1,
                    'unit_price' => 30000,
                ],
            ],
        ], $staff);

        $this->assertSame('counter', $order->purchase_type);
        $this->assertTrue($order->isCounter());
        $this->assertNull($order->online_request_id);
    }

    /**
     * T05: Online accept twice creates exactly 1 Order (idempotent).
     */
    public function test_online_order_request_acceptance_is_idempotent_and_creates_single_order(): void
    {
        $staff = $this->createStaff();
        [, $variant] = $this->createProductWithVariant('Latte Đá', 45000);
        $topping = $this->createTopping('Kem cheese', 12000);

        $token = bin2hex(random_bytes(32));
        $request = OnlineOrderRequest::forceCreate([
            'request_code' => 'REQ-TEST-001',
            'purchase_type' => 'online',
            'contact_name' => 'Nguyễn Văn Test',
            'contact_phone' => '0988776655',
            'payment_preference' => 'cash',
            'payment_timing' => 'on_receipt',
            'status' => 'pending',
            'submission_key' => hash('sha256', 'key1'),
            'tracking_token_hash' => hash('sha256', $token),
        ]);

        $this->actingAs($staff, 'web')->get(route('staff.online-requests.index'))
            ->assertOk()->assertSee('0988776655');

        $item = OnlineOrderRequestItem::forceCreate([
            'request_id' => $request->id,
            'variant_id' => $variant->id,
            'quantity' => 2,
            'product_name' => 'Latte Đá',
            'variant_name' => 'Size M',
            'unit_price' => 45000,
        ]);

        OnlineOrderRequestItemTopping::forceCreate([
            'request_item_id' => $item->id,
            'topping_id' => $topping->id,
            'quantity' => 1,
            'topping_name' => 'Kem cheese',
            'price' => 12000,
        ]);

        $service = app(OrderService::class);

        // First accept
        $order1 = $service->acceptOnlineRequest($request, $staff);
        $this->assertInstanceOf(Order::class, $order1);
        $this->assertSame('online', $order1->purchase_type);
        $this->assertSame($request->id, $order1->online_request_id);
        $this->assertSame('accepted', $request->fresh()->status);
        $this->assertSame($staff->id, $request->fresh()->accepted_by);

        // Check order items and toppings converted correctly: unit 45k * 2 = 90k + topping 12k = 102k
        $this->assertEquals(102000, (float) $order1->total_amount);
        $this->assertCount(1, $order1->orderItems);
        $this->assertCount(1, $order1->orderItems->first()->orderItemToppings);

        // Second accept (simulate double-click or replay)
        $order2 = $service->acceptOnlineRequest($request, $staff);
        $this->assertSame($order1->id, $order2->id);
        $this->assertSame(1, Order::where('online_request_id', $request->id)->count());
    }

    /**
     * T06: Online reject does NOT create Order.
     */
    public function test_online_order_request_rejection_does_not_create_order(): void
    {
        $staff = $this->createStaff();
        $token = bin2hex(random_bytes(32));
        $request = OnlineOrderRequest::forceCreate([
            'request_code' => 'REQ-TEST-REJECT',
            'purchase_type' => 'online',
            'contact_name' => 'Khách Từ Chối',
            'contact_phone' => '0911223344',
            'payment_preference' => 'cash',
            'payment_timing' => 'on_receipt',
            'status' => 'pending',
            'submission_key' => hash('sha256', 'key-rej'),
            'tracking_token_hash' => hash('sha256', $token),
        ]);

        $service = app(OrderService::class);
        $result = $service->rejectOnlineRequest($request, 'Hết nguyên liệu pha chế món này', $staff);

        $this->assertInstanceOf(OnlineOrderRequest::class, $result);
        $this->assertSame('rejected', $request->fresh()->status);
        $this->assertSame('Hết nguyên liệu pha chế món này', $request->fresh()->rejection_reason);
        $this->assertSame(0, Order::where('online_request_id', $request->id)->count());
    }

    /**
     * T07: Scheduled outside opening hours (07:00-22:00) is rejected.
     */
    public function test_scheduled_pickup_outside_opening_hours_is_rejected(): void
    {
        [, $variant] = $this->createProductWithVariant();
        $this->post(route('cart.add'), ['variant_id' => $variant->id, 'quantity' => 1]);

        // Place at 04:30 AM tomorrow
        $earlyTime = now('Asia/Ho_Chi_Minh')->addDay()->setTime(4, 30, 0)->format('Y-m-d H:i:s');
        $response = $this->post(route('cart.place'), [
            'purchase_type' => 'scheduled',
            'customer_name' => 'Khách Sáng Sớm',
            'customer_phone' => '0933445566',
            'pickup_at' => $earlyTime,
        ]);

        $response->assertSessionHasErrors('pickup_at');
        $this->assertDatabaseCount('online_order_requests', 0);
    }

    /**
     * T08: Accept request does NOT deduct inventory.
     */
    public function test_accepting_online_request_does_not_deduct_inventory(): void
    {
        $staff = $this->createStaff();
        [, $variant] = $this->createProductWithVariant();
        $ingredient = Ingredient::forceCreate([
            'name' => 'Hạt Robusta',
            'unit' => 'g',
            'current_stock' => 5000,
            'status' => 'active',
        ]);
        ProductRecipe::forceCreate([
            'variant_id' => $variant->id,
            'ingredient_id' => $ingredient->id,
            'quantity' => 20, // 20g per cup
        ]);

        $request = OnlineOrderRequest::forceCreate([
            'request_code' => 'REQ-TEST-STOCK',
            'purchase_type' => 'online',
            'contact_name' => 'Khách Kho',
            'contact_phone' => '0909090909',
            'payment_preference' => 'cash',
            'payment_timing' => 'on_receipt',
            'status' => 'pending',
            'submission_key' => hash('sha256', 'key-stock'),
            'tracking_token_hash' => hash('sha256', 'token-stock'),
        ]);

        OnlineOrderRequestItem::forceCreate([
            'request_id' => $request->id,
            'variant_id' => $variant->id,
            'quantity' => 2,
            'product_name' => 'Cà phê',
            'variant_name' => 'M',
            'unit_price' => 35000,
        ]);

        $service = app(OrderService::class);
        $order = $service->acceptOnlineRequest($request, $staff);

        // Verify stock is untouched on accept
        $this->assertSame('5000.000', $ingredient->fresh()->current_stock);
        $this->assertSame(0, DB::table('inventory_transactions')->count());
        $order->load('orderItems');
        $this->assertSame('pending', $order->orderItems->first()->status);
    }

    /**
     * T09 & T10: Transition pending -> preparing deducts inventory via H06, idempotent on retry.
     */
    public function test_transition_to_preparing_deducts_inventory_and_is_idempotent(): void
    {
        $staff = $this->createStaff();
        [, $variant] = $this->createProductWithVariant();
        $ingredient = Ingredient::forceCreate([
            'name' => 'Sữa đặc Ngôi sao',
            'unit' => 'ml',
            'current_stock' => 1000,
            'status' => 'active',
        ]);
        ProductRecipe::forceCreate([
            'variant_id' => $variant->id,
            'ingredient_id' => $ingredient->id,
            'quantity' => 30, // 30ml per cup
        ]);

        $order = Order::forceCreate([
            'order_code' => 'ORD-BREW-01',
            'purchase_type' => 'online',
            'user_id' => $staff->id,
            'status' => 'pending',
            'total_amount' => 70000,
            'ordered_at' => now(),
        ]);

        $item = OrderItem::forceCreate([
            'order_id' => $order->id,
            'product_id' => $variant->product_id,
            'variant_id' => $variant->id,
            'product_name' => 'Cà phê sữa',
            'quantity' => 2,
            'unit_price' => 35000,
            'subtotal' => 70000,
            'status' => 'pending',
        ]);

        $route = route('staff.orders.items.status.update', [$order, $item]);

        // Transition pending -> preparing
        $this->actingAs($staff)->patch($route, ['status' => 'preparing'])
            ->assertRedirect(route('staff.orders.show', $order));

        $this->assertSame('preparing', $item->fresh()->status);
        // Deducted: 30ml * 2 = 60ml. Remaining: 1000 - 60 = 940ml.
        $this->assertSame('940.000', $ingredient->fresh()->current_stock);
        $this->assertSame(1, DB::table('inventory_transactions')->count());

        // Retry preparing: idempotent, must not deduct twice
        $this->patchJson($route, ['status' => 'preparing'])->assertUnprocessable();
        $this->assertSame('940.000', $ingredient->fresh()->current_stock);
        $this->assertSame(1, DB::table('inventory_transactions')->count());
    }

    /**
     * T11: Insufficient stock prevents transition to preparing.
     */
    public function test_insufficient_stock_prevents_transition_to_preparing(): void
    {
        $staff = $this->createStaff();
        [, $variant] = $this->createProductWithVariant();
        $ingredient = Ingredient::forceCreate([
            'name' => 'Hạt Arabica Cầu Đất',
            'unit' => 'g',
            'current_stock' => 10, // Only 10g left
            'status' => 'active',
        ]);
        ProductRecipe::forceCreate([
            'variant_id' => $variant->id,
            'ingredient_id' => $ingredient->id,
            'quantity' => 20, // Requires 20g
        ]);

        $order = Order::forceCreate([
            'order_code' => 'ORD-BREW-LOW',
            'user_id' => $staff->id,
            'status' => 'pending',
            'total_amount' => 35000,
            'ordered_at' => now(),
        ]);

        $item = OrderItem::forceCreate([
            'order_id' => $order->id,
            'product_id' => $variant->product_id,
            'variant_id' => $variant->id,
            'product_name' => 'Espresso',
            'quantity' => 1,
            'unit_price' => 35000,
            'subtotal' => 35000,
            'status' => 'pending',
        ]);

        $route = route('staff.orders.items.status.update', [$order, $item]);
        $response = $this->actingAs($staff)->patchJson($route, ['status' => 'preparing']);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('ingredient');
        $this->assertSame('pending', $item->fresh()->status);
        $this->assertSame('10.000', $ingredient->fresh()->current_stock);
        $this->assertSame(0, DB::table('inventory_transactions')->count());
    }

    /**
     * T12, T13, T14: Order completion requires all items served and fully paid.
     */
    public function test_order_completion_lifecycle_requires_served_and_paid(): void
    {
        $staff = $this->createStaff();
        [, $variant] = $this->createProductWithVariant();

        $order = Order::forceCreate([
            'order_code' => 'ORD-CYCLE-01',
            'purchase_type' => 'online',
            'user_id' => $staff->id,
            'status' => 'pending',
            'subtotal' => 35000,
            'total_amount' => 35000,
            'ordered_at' => now(),
        ]);

        $item = OrderItem::forceCreate([
            'order_id' => $order->id,
            'product_id' => $variant->product_id,
            'variant_id' => $variant->id,
            'product_name' => 'Cà phê',
            'quantity' => 1,
            'unit_price' => 35000,
            'subtotal' => 35000,
            'status' => 'pending',
        ]);

        $service = app(OrderService::class);

        // Case 1: Unserved items -> cannot complete
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('phục vụ/giao');
        $service->complete($order, $staff);
    }

    public function test_order_completion_blocked_when_unpaid(): void
    {
        $staff = $this->createStaff();
        [, $variant] = $this->createProductWithVariant();

        $order = Order::forceCreate([
            'order_code' => 'ORD-CYCLE-02',
            'purchase_type' => 'online',
            'user_id' => $staff->id,
            'status' => 'pending',
            'subtotal' => 35000,
            'total_amount' => 35000,
            'ordered_at' => now(),
        ]);

        $item = OrderItem::forceCreate([
            'order_id' => $order->id,
            'product_id' => $variant->product_id,
            'variant_id' => $variant->id,
            'product_name' => 'Cà phê',
            'quantity' => 1,
            'unit_price' => 35000,
            'subtotal' => 35000,
            'status' => 'served', // Item served
        ]);

        $service = app(OrderService::class);

        // Case 2: Served but unpaid -> cannot complete
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('thu đủ tiền');
        $service->complete($order, $staff);
    }

    public function test_order_completion_succeeds_when_served_and_paid(): void
    {
        $staff = $this->createStaff();
        [, $variant] = $this->createProductWithVariant();

        $order = Order::forceCreate([
            'order_code' => 'ORD-CYCLE-03',
            'purchase_type' => 'online',
            'user_id' => $staff->id,
            'status' => 'pending',
            'subtotal' => 35000,
            'total_amount' => 35000,
            'ordered_at' => now(),
        ]);

        $item = OrderItem::forceCreate([
            'order_id' => $order->id,
            'product_id' => $variant->product_id,
            'variant_id' => $variant->id,
            'product_name' => 'Cà phê',
            'quantity' => 1,
            'unit_price' => 35000,
            'subtotal' => 35000,
            'status' => 'served',
        ]);

        $service = app(OrderService::class);

        // Finance-owned settlement fixture; Sales must not create this payment.
        $payment = Payment::forceCreate([
            'order_id' => $order->id,
            'payment_code' => 'FINANCE-TEST-SETTLED',
            'payment_method' => 'cash',
            'amount' => 35000,
            'status' => 'paid',
            'paid_at' => now(),
            'created_by' => $staff->id,
        ]);

        $this->assertSame('paid', $payment->status);
        $this->assertTrue($order->isPaidInFull());
        $this->assertTrue($order->allItemsServed());

        // Complete order
        $completedOrder = $service->complete($order, $staff);
        $this->assertSame('completed', $completedOrder->status);
        $this->assertNotNull($completedOrder->completed_at);
    }

    public function test_sales_payment_write_is_blocked_without_finance_contract(): void
    {
        $staff = $this->createStaff();
        $order = Order::forceCreate([
            'order_code' => 'FINANCE-BLOCKED', 'user_id' => $staff->id,
            'status' => 'pending', 'total_amount' => 35000, 'ordered_at' => now(),
        ]);
        try {
            app(OrderService::class)->recordPayment($order, ['payment_method' => 'cash'], $staff);
            $this->fail('Sales must not write Finance data.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('payment', $e->errors());
        }
        $this->assertDatabaseCount('payments', 0);
    }

    /**
     * T15: SHA-256 tracking token lookup succeeds; invalid token returns 404.
     */
    public function test_tracking_order_with_valid_token_succeeds_and_invalid_token_returns_404(): void
    {
        $plainToken = bin2hex(random_bytes(32));
        $hash = hash('sha256', $plainToken);

        $request = OnlineOrderRequest::forceCreate([
            'request_code' => 'REQ-TRACK-TEST',
            'purchase_type' => 'online',
            'contact_name' => 'Khách Tra Cứu',
            'contact_phone' => '0977889900',
            'payment_preference' => 'cash',
            'payment_timing' => 'on_receipt',
            'status' => 'pending',
            'submission_key' => hash('sha256', 'sub-track'),
            'tracking_token_hash' => $hash,
        ]);

        // Valid token lookup
        $response = $this->get(route('orders.track', ['token' => $plainToken]));
        $response->assertOk();
        $response->assertSee('REQ-TRACK-TEST');
        $response->assertSee('Khách Tra Cứu');
        $response->assertSee('quán tiếp nhận');

        // Tampered token returns 404
        $invalidToken = bin2hex(random_bytes(32));
        $this->get(route('orders.track', ['token' => $invalidToken]))->assertNotFound();
    }

    private function submitPayload(): array
    {
        return ['purchase_type' => 'online', 'customer_name' => 'HTTP Customer', 'customer_phone' => '0901234567'];
    }

    public function test_customer_prepaid_selection_creates_only_a_pending_request_until_staff_confirms_payment(): void
    {
        [, $variant] = $this->createProductWithVariant();
        $this->post(route('cart.add'), ['variant_id' => $variant->id, 'quantity' => 1])->assertSessionHasNoErrors();
        $this->get(route('cart.checkout'))->assertOk();

        $this->post(route('cart.place'), $this->submitPayload() + [
            'payment_option' => 'bank_transfer_prepaid',
            'prepaid_transfer_declaration' => '1',
        ])->assertRedirect();

        $this->assertDatabaseHas('online_order_requests', [
            'payment_preference' => 'bank_transfer',
            'payment_timing' => 'prepaid',
            'status' => 'pending',
        ]);
        $this->assertDatabaseCount('payments', 0);
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_customer_cannot_select_prepaid_without_acknowledging_staff_verification(): void
    {
        [, $variant] = $this->createProductWithVariant();
        $this->post(route('cart.add'), ['variant_id' => $variant->id, 'quantity' => 1])->assertSessionHasNoErrors();
        $this->get(route('cart.checkout'))->assertOk();

        $this->postJson(route('cart.place'), $this->submitPayload() + [
            'payment_option' => 'bank_transfer_prepaid',
        ])->assertUnprocessable()->assertJsonValidationErrors('prepaid_transfer_declaration');

        $this->assertDatabaseCount('online_order_requests', 0);
        $this->assertDatabaseCount('payments', 0);
    }

    public function test_submit_retry_is_bound_to_same_intent_and_changed_contact_is_rejected(): void
    {
        [, $variant] = $this->createProductWithVariant();
        $this->post(route('cart.add'), ['variant_id' => $variant->id, 'quantity' => 2])->assertSessionHasNoErrors();
        $this->get(route('cart.checkout'))->assertOk();
        $key = session('checkout_intent.key');
        $payload = $this->submitPayload() + ['submission_key' => $key];
        $first = $this->post(route('cart.place'), $payload)->assertRedirect();
        $this->post(route('cart.place'), $payload)->assertRedirect($first->headers->get('Location'));
        $this->assertDatabaseCount('online_order_requests', 1);
        $this->postJson(route('cart.place'), array_replace($payload, ['customer_name' => 'Different']))->assertUnprocessable();
        $this->assertDatabaseCount('online_order_requests', 1);
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_changed_cart_rotates_intent_and_same_signature_merges(): void
    {
        [, $variant] = $this->createProductWithVariant();
        $this->post(route('cart.add'), ['variant_id' => $variant->id, 'quantity' => 1]);
        $this->get(route('cart.checkout'))->assertOk();
        $old = session('checkout_intent.key');
        $this->post(route('cart.add'), ['variant_id' => $variant->id, 'quantity' => 1]);
        $this->get(route('cart.checkout'))->assertOk();
        $this->assertNotSame($old, session('checkout_intent.key'));
        $this->assertCount(1, session('customer_cart'));
        $this->postJson(route('cart.place'), $this->submitPayload() + ['submission_key' => $old])->assertUnprocessable();
        $this->post(route('cart.place'), $this->submitPayload())->assertRedirect();
        $this->assertSame(2, (int) OnlineOrderRequestItem::firstOrFail()->quantity);
    }

    public function test_equal_total_different_line_prices_still_requires_new_quote(): void
    {
        [, $a] = $this->createProductWithVariant('A', 30000);
        [, $b] = $this->createProductWithVariant('B', 40000);
        foreach ([$a, $b] as $variant) {
            $this->post(route('cart.add'), ['variant_id' => $variant->id, 'quantity' => 1]);
        }
        $this->get(route('cart.checkout'))->assertOk();
        $a->update(['price' => 35000]);
        $b->update(['price' => 35000]);
        $this->postJson(route('cart.place'), $this->submitPayload() + ['confirm_price_change' => '1'])->assertUnprocessable();
        $this->assertDatabaseCount('online_order_requests', 0);
    }

    public function test_unavailable_topping_and_product_are_rejected_without_silent_removal(): void
    {
        [, $variant] = $this->createProductWithVariant();
        $topping = $this->createTopping('Disabled', 1000);
        $topping->forceFill(['status' => 'unavailable'])->saveOrFail();
        $this->postJson(route('cart.add'), ['variant_id' => $variant->id, 'quantity' => 1, 'toppings' => [$topping->id]])->assertUnprocessable();
        $this->post(route('cart.add'), ['variant_id' => $variant->id, 'quantity' => 1]);
        $this->get(route('cart.checkout'))->assertOk();
        $variant->product->forceFill(['status' => 'unavailable'])->saveOrFail();
        $this->postJson(route('cart.place'), $this->submitPayload())->assertUnprocessable();
        $this->get(route('pickup.cart.index'))->assertOk()->assertSee('Xóa dòng món')->assertDontSee('Tiếp tục đặt món');
        $this->assertDatabaseCount('online_order_requests', 0);
    }

    public function test_http_handover_requires_payment_and_code_and_does_not_touch_other_items(): void
    {
        $staff = $this->createStaff();
        [, $variant] = $this->createProductWithVariant();
        $this->post(route('cart.add'), ['variant_id' => $variant->id, 'quantity' => 1]);
        $this->get(route('cart.checkout'))->assertOk();
        $this->post(route('cart.place'), $this->submitPayload())->assertRedirect();
        $request = OnlineOrderRequest::firstOrFail();
        $order = app(OrderService::class)->acceptOnlineRequest($request, $staff);
        $item = $order->orderItems()->firstOrFail();
        $other = app(OrderService::class)->addItem($order, ['variant_id' => $variant->id, 'quantity' => 1], $staff);
        $ingredient = Ingredient::forceCreate(['name' => 'HTTP beans', 'unit' => 'g', 'status' => 'active', 'current_stock' => '100.000']);
        ProductRecipe::forceCreate(['variant_id' => $variant->id, 'ingredient_id' => $ingredient->id, 'quantity' => '20.000']);
        $url = route('staff.orders.items.status.update', [$order, $item]);
        $this->patchJson($url, ['status' => 'preparing'])->assertUnauthorized();
        $this->actingAs($staff)->patchJson($url, ['status' => 'served'])->assertUnprocessable();
        $this->patch($url, ['status' => 'preparing'])->assertSessionHasNoErrors();
        $this->patch($url, ['status' => 'ready'])->assertSessionHasNoErrors();
        $this->patchJson($url, ['status' => 'served', 'pickup_code' => $request->request_code])->assertUnprocessable()->assertJsonValidationErrors('payment');
        Payment::forceCreate(['order_id' => $order->id, 'payment_code' => 'HTTP-SETTLEMENT', 'payment_method' => 'cash', 'amount' => $order->fresh()->total_amount, 'status' => 'paid', 'paid_at' => now(), 'created_by' => $staff->id]);
        $this->patchJson($url, ['status' => 'served', 'pickup_code' => 'WRONG'])->assertUnprocessable()->assertJsonValidationErrors('pickup_code');
        $this->patch($url, ['status' => 'served', 'pickup_code' => $request->request_code])->assertSessionHasNoErrors();
        $this->patchJson($url, ['status' => 'served', 'pickup_code' => $request->request_code])->assertUnprocessable();
        $this->assertSame('served', $item->fresh()->status);
        $this->assertSame('pending', $other->fresh()->status);
        $this->assertSame('pending', $order->fresh()->status);
        $this->assertSame('80.000', $ingredient->fresh()->current_stock);
        $this->assertDatabaseCount('inventory_transactions', 1);
        $this->patchJson(route('staff.orders.complete', $order))->assertUnprocessable();
        $this->postJson(route('staff.orders.items.store', $order), ['variant_id' => $variant->id, 'quantity' => 1])->assertUnprocessable();
        $this->patchJson(route('staff.orders.items.update', [$order, $other]), ['quantity' => 2])->assertUnprocessable();
        $this->deleteJson(route('staff.orders.items.destroy', [$order, $other]))->assertUnprocessable();
    }

    public function test_duplicate_pos_operation_creates_only_one_order_and_rejects_changed_payload(): void
    {
        $staff = $this->createStaff();
        [, $variant] = $this->createProductWithVariant();
        $service = app(OrderService::class);
        $items = [['variant_id' => $variant->id, 'quantity' => 1]];
        $first = $service->createPosOnce(['order_type' => 'takeaway'], $items, $staff, 'stable-operation');
        $second = $service->createPosOnce(['order_type' => 'takeaway'], $items, $staff, 'stable-operation');
        $this->assertSame($first->id, $second->id);
        $this->assertDatabaseCount('orders', 1);
        $this->expectException(ValidationException::class);
        $service->createPosOnce(['order_type' => 'takeaway'], [['variant_id' => $variant->id, 'quantity' => 2]], $staff, 'stable-operation');
    }

    public function test_settlement_comparison_distinguishes_one_cent_without_float_rounding(): void
    {
        $order = new Order;
        $order->forceFill(['total_amount' => '90071992547409.91']);
        $payment = new Payment;
        $payment->forceFill(['status' => 'paid', 'amount' => '90071992547409.90']);
        $order->setRelation('payments', collect([$payment]));
        $this->assertFalse($order->isPaidInFull());
        $payment->forceFill(['amount' => '90071992547409.91']);
        $this->assertTrue($order->isPaidInFull());
    }
}
