<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\CafeTable;
use App\Models\Ingredient;
use App\Models\InventoryTransaction;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\ProductRecipe;
use App\Models\Promotion;
use App\Models\User;
use App\Services\OrderService;
use App\Services\PaymentService;
use App\Services\PromotionService;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PaymentWorkflowTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame('sqlite', DB::connection()->getDriverName());
        $this->assertSame(':memory:', DB::connection()->getDatabaseName());
        foreach (['0001_01_01_000000_create_users_table.php', '2026_08_14_000002_create_areas_table.php',
            '2026_08_14_000005_create_ingredients_table.php', '2026_08_14_000006_create_cafe_tables_table.php',
            '2026_08_14_000008_create_promotions_table.php'] as $migration) {
            (require database_path('migrations/'.$migration))->up();
        }
        // SQLite mirrors the transaction tables without MySQL-only ALTER CHECK syntax.
        Schema::create('orders', function (Blueprint $table): void {
            $table->id();
            $table->string('order_code', 50)->unique();
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('table_id')->nullable()->constrained('cafe_tables');
            $table->string('order_type')->default('takeaway');
            $table->string('status')->default('pending');
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
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('variant_id');
            $table->string('product_name');
            $table->string('variant_name')->nullable();
            $table->integer('quantity');
            $table->decimal('unit_price', 12, 2);
            $table->decimal('topping_amount', 12, 2)->default(0);
            $table->decimal('subtotal', 15, 2);
            $table->string('status');
            $table->timestamps();
        });
        Schema::create('order_item_toppings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_item_id')->constrained('order_items');
            $table->unsignedBigInteger('topping_id');
            $table->string('topping_name');
            $table->decimal('price', 12, 2);
            $table->integer('quantity');
            $table->unique(['order_item_id', 'topping_id']);
            $table->timestamps();
        });
        Schema::create('payments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id')->constrained('orders');
            $table->string('payment_code', 50)->unique();
            $table->string('payment_method');
            $table->decimal('amount', 15, 2);
            $table->string('transaction_code', 100)->nullable();
            $table->string('status')->default('pending');
            $table->dateTime('paid_at')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });
        Schema::create('order_promotions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id')->unique()->constrained('orders');
            $table->foreignId('promotion_id')->constrained('promotions');
            $table->string('promotion_code', 50);
            $table->decimal('discount_amount', 15, 2);
            $table->timestamps();
        });
        Schema::create('product_recipes', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('variant_id');
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
        $this->withoutVite();
    }

    private function actor(string $role = 'staff', string $status = 'active'): User
    {
        return User::forceCreate(['name' => 'Thu ngân', 'email' => uniqid().'@example.test',
            'password' => 'test-only-password', 'role' => $role, 'status' => $status]);
    }

    private function order(User $actor, ?CafeTable $table = null, string $itemStatus = 'served'): Order
    {
        $order = Order::forceCreate(['order_code' => uniqid('ORD-'), 'user_id' => $actor->id,
            'table_id' => $table?->id, 'order_type' => $table ? 'dine_in' : 'takeaway',
            'status' => 'pending', 'subtotal' => '25.10', 'discount_amount' => '0.00',
            'tax_amount' => '0.00', 'total_amount' => '25.10', 'ordered_at' => now()]);
        OrderItem::forceCreate(['order_id' => $order->id, 'product_id' => 1, 'variant_id' => 1,
            'product_name' => 'Snapshot Latte', 'variant_name' => 'Lớn', 'quantity' => 2,
            'unit_price' => '12.55', 'topping_amount' => '0.00', 'subtotal' => '25.10', 'status' => $itemStatus]);

        return $order;
    }

    private function table(string $status = 'occupied'): CafeTable
    {
        $area = Area::forceCreate(['name' => uniqid('Area-'), 'status' => 'active']);

        return CafeTable::forceCreate(['area_id' => $area->id, 'table_code' => uniqid('T-'), 'status' => $status]);
    }

    private function pay(Order $order, array $attributes = []): TestResponse
    {
        return $this->post(route('staff.orders.payments.store', $order), array_replace([
            'payment_method' => 'cash', 'received_confirmation' => '1',
        ], $attributes));
    }

    private function attachPromotion(Order $order): Promotion
    {
        $promotion = Promotion::forceCreate(['code' => strtoupper(uniqid('P-')), 'name' => 'Giảm giá',
            'discount_type' => 'fixed', 'discount_value' => '1.10', 'minimum_order' => '0.00',
            'start_at' => now()->subDay(), 'end_at' => now()->addDay(),
            'status' => 'active', 'usage_limit' => 1, 'used_count' => 0]);
        app(PromotionService::class)->applyToOrder($order, $promotion->code);

        return $promotion;
    }

    private function stockFor(Order $order, string $stock = '10.000'): Ingredient
    {
        $ingredient = Ingredient::forceCreate(['name' => uniqid('Coffee-'), 'unit' => 'g',
            'current_stock' => $stock, 'minimum_stock' => '0.000', 'cost_price' => '1.00', 'status' => 'active']);
        ProductRecipe::forceCreate(['variant_id' => $order->orderItems()->sole()->variant_id,
            'ingredient_id' => $ingredient->id, 'quantity' => '2.500']);

        return $ingredient;
    }

    public function test_pos_prepaid_cash_is_paid_then_preparing_without_completing_or_releasing_table(): void
    {
        $actor = $this->actor();
        $table = $this->table();
        $order = $this->order($actor, $table, 'pending');
        $ingredient = $this->stockFor($order);
        $promotion = $this->attachPromotion($order);

        $payment = DB::transaction(fn (): Payment => app(PaymentService::class)
            ->recordPosPrepaymentAndStartPreparing($order, [
                'payment_method' => 'cash', 'received_confirmation' => true,
                'amount' => '1.00', 'status' => 'failed', 'created_by' => 999,
            ], $actor));

        $this->assertSame('paid', $payment->status);
        $this->assertSame('24.00', $payment->amount);
        $this->assertSame($actor->id, $payment->created_by);
        $this->assertSame('pending', $order->fresh()->status);
        $this->assertNull($order->fresh()->completed_at);
        $this->assertSame('occupied', $table->fresh()->status);
        $this->assertSame('preparing', $order->orderItems()->sole()->status);
        $this->assertSame('5.000', $ingredient->fresh()->current_stock);
        $ledger = InventoryTransaction::sole();
        $this->assertSame('5.000', $ledger->quantity);
        $this->assertSame('order_item', $ledger->reference_type);
        $this->assertSame($order->orderItems()->sole()->id, $ledger->reference_id);
        $this->assertSame($actor->id, $ledger->created_by);
        $this->assertSame(1, $promotion->fresh()->used_count);
    }

    public function test_prepaid_order_completes_and_releases_table_only_after_final_item_is_served(): void
    {
        $actor = $this->actor();
        $table = $this->table();
        $order = $this->order($actor, $table, 'pending');
        $this->stockFor($order);
        DB::transaction(fn (): Payment => app(PaymentService::class)
            ->recordPosPrepaymentAndStartPreparing($order, [
                'payment_method' => 'cash', 'received_confirmation' => true,
            ], $actor));

        $item = $order->orderItems()->sole();
        $service = app(OrderService::class);
        $service->transitionItemStatus($order, $item, 'ready', $actor);
        $this->assertSame('pending', $order->fresh()->status);
        $this->assertSame('occupied', $table->fresh()->status);
        $service->transitionItemStatus($order, $item, 'served', $actor);
        $this->assertSame('completed', $order->fresh()->status);
        $this->assertNotNull($order->fresh()->completed_at);
        $this->assertSame('available', $table->fresh()->status);
        $this->assertDatabaseCount('payments', 1);
        $this->assertDatabaseCount('inventory_transactions', 1);
    }

    public function test_serving_an_unpaid_order_does_not_complete_it(): void
    {
        $actor = $this->actor();
        $order = $this->order($actor, null, 'ready');
        app(OrderService::class)->transitionItemStatus($order, $order->orderItems()->sole(), 'served', $actor);
        $this->assertSame('pending', $order->fresh()->status);
        $this->assertNull($order->fresh()->completed_at);
    }

    public function test_pos_prepaid_bank_transfer_requires_confirmation_and_keeps_old_payment_history(): void
    {
        $actor = $this->actor();
        $order = $this->order($actor, null, 'pending');
        $this->stockFor($order);
        Payment::forceCreate(['order_id' => $order->id, 'payment_code' => 'OLD-FAILED',
            'payment_method' => 'bank_transfer', 'amount' => '25.10', 'status' => 'failed',
            'created_by' => $actor->id]);

        try {
            DB::transaction(fn (): Payment => app(PaymentService::class)
                ->recordPosPrepaymentAndStartPreparing($order, ['payment_method' => 'bank_transfer'], $actor));
            $this->fail('Expected missing confirmation to be rejected.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('payment', $exception->errors());
        }
        $this->assertDatabaseCount('payments', 1);

        $payment = DB::transaction(fn (): Payment => app(PaymentService::class)
            ->recordPosPrepaymentAndStartPreparing($order, [
                'payment_method' => 'bank_transfer', 'received_confirmation' => true,
                'transaction_code' => ' BANK-42 ',
            ], $actor));
        $this->assertSame('BANK-42', $payment->transaction_code);
        $this->assertDatabaseHas('payments', ['payment_code' => 'OLD-FAILED', 'status' => 'failed']);
        $this->assertDatabaseCount('payments', 2);
    }

    public function test_pos_prepaid_stock_failure_rolls_back_payment_promotion_and_item(): void
    {
        $actor = $this->actor();
        $order = $this->order($actor, null, 'pending');
        $ingredient = $this->stockFor($order, '1.000');
        $promotion = $this->attachPromotion($order);

        try {
            DB::transaction(fn (): Payment => app(PaymentService::class)
                ->recordPosPrepaymentAndStartPreparing($order, [
                    'payment_method' => 'cash', 'received_confirmation' => true,
                ], $actor));
            $this->fail('Expected insufficient stock to be rejected.');
        } catch (ValidationException $exception) {
            $this->assertNotEmpty($exception->errors());
        }

        $this->assertDatabaseCount('payments', 0);
        $this->assertDatabaseCount('inventory_transactions', 0);
        $this->assertSame('pending', $order->orderItems()->sole()->status);
        $this->assertSame('1.000', $ingredient->fresh()->current_stock);
        $this->assertSame(0, $promotion->fresh()->used_count);
    }

    public function test_pos_prepaid_failure_rolls_back_a_new_order_created_in_the_outer_transaction(): void
    {
        $actor = $this->actor();
        $ingredient = null;

        try {
            DB::transaction(function () use ($actor, &$ingredient): void {
                $order = $this->order($actor, null, 'pending');
                $ingredient = $this->stockFor($order, '1.000');
                app(PaymentService::class)->recordPosPrepaymentAndStartPreparing($order, [
                    'payment_method' => 'cash', 'received_confirmation' => true,
                ], $actor);
            });
            $this->fail('Expected checkout to roll back when stock is insufficient.');
        } catch (ValidationException $exception) {
            $this->assertNotEmpty($exception->errors());
        }

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('order_items', 0);
        $this->assertDatabaseCount('payments', 0);
        $this->assertDatabaseCount('inventory_transactions', 0);
        $this->assertDatabaseMissing('ingredients', ['id' => $ingredient?->id]);
    }

    public function test_pos_prepaid_requires_outer_transaction_and_cannot_pay_twice(): void
    {
        $actor = $this->actor();
        $order = $this->order($actor, null, 'pending');
        $this->stockFor($order);
        $service = app(PaymentService::class);
        $attributes = ['payment_method' => 'cash', 'received_confirmation' => true];

        try {
            $service->recordPosPrepaymentAndStartPreparing($order, $attributes, $actor);
            $this->fail('Expected outer transaction requirement.');
        } catch (\DomainException $exception) {
            $this->assertStringContainsString('transaction', $exception->getMessage());
        }
        DB::transaction(fn (): Payment => $service->recordPosPrepaymentAndStartPreparing($order, $attributes, $actor));
        try {
            DB::transaction(fn (): Payment => $service->recordPosPrepaymentAndStartPreparing($order, $attributes, $actor));
            $this->fail('Expected duplicate payment to be rejected.');
        } catch (ValidationException $exception) {
            $this->assertNotEmpty($exception->errors());
        }
        $this->assertDatabaseCount('payments', 1);
        $this->assertDatabaseCount('inventory_transactions', 1);
    }

    public function test_paid_pending_order_cannot_be_mutated_or_cancelled(): void
    {
        $actor = $this->actor();
        $order = $this->order($actor, null, 'pending');
        $item = $order->orderItems()->sole();
        Payment::forceCreate(['order_id' => $order->id, 'payment_code' => 'PAID-PENDING',
            'payment_method' => 'cash', 'amount' => '25.10', 'status' => 'paid',
            'paid_at' => now(), 'created_by' => $actor->id]);
        $service = app(OrderService::class);

        foreach ([
            fn () => $service->addItem($order, ['variant_id' => 1, 'quantity' => 1], $actor),
            fn () => $service->updateItemQuantity($order, $item, ['quantity' => 3], $actor),
            fn () => $service->removeItem($order, $item, $actor),
            fn () => $service->addItemTopping($order, $item, ['topping_id' => 1, 'quantity' => 1], $actor),
            fn () => $service->cancel($order, $actor),
        ] as $mutation) {
            try {
                $mutation();
                $this->fail('Expected a paid Order to reject mutation.');
            } catch (ValidationException $exception) {
                $this->assertArrayHasKey('order', $exception->errors());
            }
        }

        $this->assertSame('pending', $order->fresh()->status);
        $this->assertSame('pending', $item->fresh()->status);
        $this->assertSame(2, $item->fresh()->quantity);
        $this->assertDatabaseCount('order_items', 1);
        $this->assertDatabaseCount('payments', 1);
    }

    public function test_cash_payment_uses_server_totals_snapshots_and_actor_then_completes_order(): void
    {
        $creator = $this->actor();
        $cashier = $this->actor();
        $order = $this->order($creator);
        $this->actingAs($cashier)->pay($order, ['amount' => '1.00', 'status' => 'failed', 'created_by' => 999,
            'total_amount' => '1.00', 'payment_code' => 'FORGED', 'paid_at' => '2000-01-01', 'user_id' => 999])
            ->assertRedirect(route('staff.orders.payments.show', $order))->assertSessionHas('success');
        $payment = Payment::sole();
        $this->assertSame('25.10', $payment->amount);
        $this->assertSame('paid', $payment->status);
        $this->assertSame($cashier->id, $payment->created_by);
        $this->assertNotSame('FORGED', $payment->payment_code);
        $this->assertTrue($payment->paid_at->isToday());
        $this->assertSame($creator->id, $order->fresh()->user_id);
        $this->assertSame('completed', $order->fresh()->status);
        $this->assertNotNull($order->fresh()->completed_at);
        $this->assertDatabaseHas('order_items', ['order_id' => $order->id, 'status' => 'served', 'product_name' => 'Snapshot Latte']);
    }

    public function test_admin_can_record_bank_transfer_with_optional_reference_after_confirmation(): void
    {
        $actor = $this->actor('admin');
        $this->actingAs($actor);
        $first = $this->order($actor);
        $this->pay($first, ['payment_method' => 'bank_transfer', 'transaction_code' => ' BANK-123 '])->assertSessionHasNoErrors();
        $this->assertSame('BANK-123', Payment::sole()->transaction_code);
        $second = $this->order($actor);
        $this->pay($second, ['payment_method' => 'bank_transfer'])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('payments', ['order_id' => $second->id, 'status' => 'paid', 'transaction_code' => null]);
    }

    public function test_validation_does_not_create_failed_attempts(): void
    {
        $actor = $this->actor();
        $order = $this->order($actor);
        $this->actingAs($actor);
        foreach ([['received_confirmation' => null], ['received_confirmation' => '0'], ['payment_method' => 'card'],
            ['transaction_code' => str_repeat('x', 101)]] as $attributes) {
            $this->pay($order, $attributes)->assertSessionHasErrors();
        }
        $this->assertDatabaseCount('payments', 0);
        $this->assertSame('pending', $order->fresh()->status);
    }

    public function test_preconditions_reject_unserved_items_and_non_pending_orders(): void
    {
        $actor = $this->actor();
        $this->actingAs($actor);
        foreach (['pending', 'preparing', 'ready', 'cancelled'] as $state) {
            $order = $this->order($actor, null, $state);
            $this->pay($order)->assertSessionHasErrors('order');
            $this->assertSame('pending', $order->fresh()->status);
        }
        foreach (['completed', 'cancelled'] as $state) {
            $order = $this->order($actor);
            $order->forceFill(['status' => $state])->save();
            $this->pay($order)->assertSessionHasErrors('order');
        }
        $this->assertDatabaseCount('payments', 0);
    }

    public function test_empty_and_zero_total_orders_are_not_paid(): void
    {
        $actor = $this->actor();
        $this->actingAs($actor);
        $empty = $this->order($actor);
        $empty->orderItems()->delete();
        $this->pay($empty)->assertSessionHasErrors('order');
        $zero = $this->order($actor);
        $zero->orderItems()->update(['unit_price' => '0.00', 'subtotal' => '0.00']);
        $this->pay($zero)->assertSessionHasErrors('order');
        $this->assertDatabaseCount('payments', 0);
        $this->assertSame('pending', $zero->fresh()->status);
    }

    public function test_repeat_submission_does_not_duplicate_payment_or_promotion_usage(): void
    {
        $actor = $this->actor();
        $order = $this->order($actor);
        $promotion = $this->attachPromotion($order);
        $this->actingAs($actor)->pay($order)->assertSessionHasNoErrors();
        $this->pay($order)->assertSessionHasErrors();
        $this->assertDatabaseCount('payments', 1);
        $this->assertSame('24.00', Payment::sole()->amount);
        $this->assertSame(1, $promotion->fresh()->used_count);
    }

    public function test_existing_paid_attempt_blocks_a_second_payment_even_if_order_is_pending(): void
    {
        $actor = $this->actor();
        $order = $this->order($actor);
        Payment::forceCreate(['order_id' => $order->id, 'payment_code' => 'OLD-PAID', 'payment_method' => 'cash',
            'amount' => '25.10', 'status' => 'paid', 'paid_at' => now(), 'created_by' => $actor->id]);
        $this->actingAs($actor)->pay($order)->assertSessionHasErrors('payment');
        $this->assertDatabaseCount('payments', 1);
    }

    public function test_table_release_respects_other_pending_orders_and_maintenance_and_takeaway(): void
    {
        $actor = $this->actor();
        $this->actingAs($actor);
        $table = $this->table();
        $order = $this->order($actor, $table);
        $this->pay($order)->assertSessionHasNoErrors();
        $this->assertSame('available', $table->fresh()->status);
        $busy = $this->table();
        $first = $this->order($actor, $busy);
        $this->order($actor, $busy);
        $this->pay($first)->assertSessionHasNoErrors();
        $this->assertSame('occupied', $busy->fresh()->status);
        $maintenance = $this->table('maintenance');
        $this->pay($this->order($actor, $maintenance))->assertSessionHasNoErrors();
        $this->pay($this->order($actor))->assertSessionHasNoErrors();
        $this->assertSame('maintenance', $maintenance->fresh()->status);
        $this->assertSame('occupied', $busy->fresh()->status);
    }

    public function test_guests_and_inactive_users_cannot_read_or_pay(): void
    {
        $order = $this->order($this->actor());
        $this->get(route('staff.orders.payments.show', $order))->assertRedirect(route('login'));
        $this->pay($order)->assertRedirect(route('login'));
        foreach (['admin', 'staff'] as $role) {
            $this->actingAs($this->actor($role, 'inactive'));
            $this->get(route('staff.orders.payments.show', $order))->assertForbidden();
            $this->pay($order)->assertForbidden();
        }
        $this->assertDatabaseCount('payments', 0);
    }

    public function test_get_checkout_is_read_only_and_history_belongs_to_its_order(): void
    {
        $actor = $this->actor('admin');
        $order = $this->order($actor);
        $other = $this->order($actor);
        Payment::forceCreate(['order_id' => $other->id, 'payment_code' => 'PRIVATE-OTHER', 'payment_method' => 'cash',
            'amount' => '25.10', 'status' => 'failed', 'created_by' => $actor->id]);
        $this->actingAs($actor)->get(route('staff.orders.payments.show', $order))
            ->assertOk()->assertSee('Xác nhận thanh toán')->assertDontSee('PRIVATE-OTHER');
        $this->assertDatabaseCount('payments', 1);
        $this->assertSame('pending', $order->fresh()->status);
    }

    public function test_pending_and_failed_history_is_preserved_when_a_new_payment_succeeds(): void
    {
        $actor = $this->actor();
        $order = $this->order($actor);
        foreach (['pending', 'failed'] as $state) {
            Payment::forceCreate(['order_id' => $order->id, 'payment_code' => 'OLD-'.$state, 'payment_method' => 'bank_transfer',
                'amount' => '25.10', 'status' => $state, 'created_by' => $actor->id]);
        }
        $this->actingAs($actor)->pay($order)->assertSessionHasNoErrors();
        $this->assertDatabaseCount('payments', 3);
        $this->assertDatabaseHas('payments', ['payment_code' => 'OLD-pending', 'status' => 'pending', 'paid_at' => null]);
        $this->assertDatabaseHas('payments', ['payment_code' => 'OLD-failed', 'status' => 'failed', 'paid_at' => null]);
    }

    public function test_exhausted_promotion_rolls_back_without_payment_or_order_completion(): void
    {
        $actor = $this->actor();
        $order = $this->order($actor);
        $promotion = $this->attachPromotion($order);
        $promotion->forceFill(['used_count' => 1])->save();
        $this->actingAs($actor)->pay($order)->assertSessionHasErrors('payment');
        $this->assertDatabaseCount('payments', 0);
        $this->assertSame('pending', $order->fresh()->status);
    }

    public function test_transaction_rolls_back_on_payment_order_table_or_usage_failure(): void
    {
        $actor = $this->actor();
        $this->actingAs($actor);
        foreach (['payment', 'order', 'table', 'promotion'] as $failure) {
            $table = $this->table();
            $order = $this->order($actor, $table);
            $promotion = $this->attachPromotion($order);
            $throw = fn () => throw new QueryException('sqlite', 'test failure', [], new \RuntimeException('injected'));
            if ($failure === 'payment') {
                Payment::creating($throw);
            }
            if ($failure === 'order') {
                Order::updating(function (Order $saving) use ($throw): void {
                    if ($saving->isDirty('status')) {
                        $throw();
                    }
                });
            }
            if ($failure === 'table') {
                CafeTable::updating($throw);
            }
            if ($failure === 'promotion') {
                Promotion::updating($throw);
            }
            try {
                $this->from(route('staff.orders.payments.show', $order))->pay($order)
                    ->assertRedirect(route('staff.orders.payments.show', $order))->assertSessionHasErrors('payment');
                $this->assertDatabaseMissing('payments', ['order_id' => $order->id]);
                $this->assertSame('pending', $order->fresh()->status);
                $this->assertNull($order->fresh()->completed_at);
                $this->assertSame('occupied', $table->fresh()->status);
                $this->assertSame(0, $promotion->fresh()->used_count);
            } finally {
                Payment::flushEventListeners();
                Order::flushEventListeners();
                CafeTable::flushEventListeners();
                Promotion::flushEventListeners();
            }
        }
    }

    public function test_service_itself_rejects_missing_received_confirmation(): void
    {
        $actor = $this->actor();
        $order = $this->order($actor);
        $this->expectException(ValidationException::class);
        app(PaymentService::class)->recordPaid($order, ['payment_method' => 'cash'], $actor);
    }

    public function test_totals_are_recalculated_from_existing_item_and_topping_snapshots(): void
    {
        $actor = $this->actor();
        $order = $this->order($actor);
        $item = $order->orderItems()->sole();
        $item->forceFill(['topping_amount' => '1.15', 'subtotal' => '26.25'])->save();
        DB::table('order_item_toppings')->insert(['order_item_id' => $item->id, 'topping_id' => 1,
            'topping_name' => 'Snapshot kem', 'price' => '1.15', 'quantity' => 1]);
        $order->forceFill(['subtotal' => '999.99', 'total_amount' => '999.99'])->save();

        $this->actingAs($actor)->pay($order)->assertSessionHasNoErrors();
        $this->assertSame('26.25', Payment::sole()->amount);
        $this->assertSame('26.25', $order->fresh()->total_amount);
        $this->assertDatabaseHas('order_item_toppings', ['order_item_id' => $item->id, 'price' => '1.15']);
        $this->get(route('staff.orders.payments.show', $order))->assertOk()
            ->assertSee('completed')->assertDontSee('id="received_confirmation"', false);
    }

    public function test_payment_preserves_an_already_applied_promotion_snapshot_after_its_expiry(): void
    {
        $actor = $this->actor();
        $order = $this->order($actor);
        $promotion = $this->attachPromotion($order);
        $promotion->forceFill(['end_at' => now()->subHour(), 'status' => 'inactive'])->save();

        $this->actingAs($actor)->pay($order)->assertSessionHasNoErrors();
        $this->assertSame('24.00', Payment::sole()->amount);
        $this->assertSame(1, $promotion->fresh()->used_count);
    }

    public function test_payment_code_collision_retries_without_duplicate_side_effects(): void
    {
        $actor = $this->actor();
        $oldOrder = $this->order($actor);
        Payment::forceCreate(['order_id' => $oldOrder->id, 'payment_code' => 'PAY-'.str_repeat('A', 24),
            'payment_method' => 'cash', 'amount' => '25.10', 'status' => 'failed', 'created_by' => $actor->id]);
        $order = $this->order($actor);
        Str::createRandomStringsUsingSequence([str_repeat('A', 24), str_repeat('B', 24)]);
        try {
            app(PaymentService::class)->recordPaid($order, ['payment_method' => 'cash', 'received_confirmation' => true], $actor);
            $this->assertDatabaseHas('payments', ['order_id' => $order->id, 'payment_code' => 'PAY-'.str_repeat('B', 24)]);
            $this->assertSame(1, Payment::query()->where('order_id', $order->id)->count());
        } finally {
            Str::createRandomStringsNormally();
        }
    }
}
