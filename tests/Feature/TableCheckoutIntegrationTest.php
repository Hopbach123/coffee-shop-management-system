<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\CafeTable;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class TableCheckoutIntegrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->assertSame('sqlite', config('database.default'));
        $this->assertSame(':memory:', DB::connection()->getDatabaseName());
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

        (require database_path('migrations/2026_08_14_000002_create_areas_table.php'))->up();
        (require database_path('migrations/2026_08_14_000006_create_cafe_tables_table.php'))->up();
        Schema::create('payments', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->string('payment_code');
            $table->string('payment_method');
            $table->decimal('amount', 15, 2);
            $table->string('status');
            $table->string('transaction_code')->nullable();
            $table->dateTime('paid_at')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->timestamps();
        });
        $this->withoutVite();
    }

    private function fixtures(string $role = 'admin'): array
    {
        $actor = User::forceCreate(['name' => 'Operator', 'email' => uniqid().'@example.test', 'password' => 'test-only', 'role' => $role, 'status' => 'active']);
        $area = Area::create(['name' => 'Garden']);
        $table = CafeTable::forceCreate(['area_id' => $area->id, 'table_code' => 'CHECK-'.uniqid(), 'status' => 'occupied']);
        $order = Order::forceCreate(['table_id' => $table->id, 'user_id' => $actor->id, 'order_code' => 'CHECK'.uniqid(), 'order_type' => 'dine_in', 'status' => 'pending', 'subtotal' => '12.35', 'discount_amount' => '2.35', 'tax_amount' => '1.00', 'total_amount' => '11.00', 'ordered_at' => now()]);
        $category = Category::create(['name' => 'Coffee']);
        $product = Product::forceCreate(['category_id' => $category->id, 'name' => 'Latte', 'slug' => uniqid(), 'status' => 'available']);
        $variant = ProductVariant::forceCreate(['product_id' => $product->id, 'name' => 'Large', 'price' => '12.35', 'status' => 'available']);
        OrderItem::forceCreate(['order_id' => $order->id, 'product_id' => $product->id, 'variant_id' => $variant->id, 'product_name' => '<b>Saved latte</b>', 'variant_name' => 'Large', 'quantity' => 1, 'unit_price' => '12.35', 'topping_amount' => '0.00', 'subtotal' => '12.35', 'status' => 'pending', 'note' => '<script>note</script>']);
        $this->actingAs($actor, 'web');

        return [$actor, $table, $order];
    }

    private function url($table, $order, string $action = 'show'): string
    {
        return route('staff.table-checkout.'.$action, [$table->id, $order->id]);
    }

    public function test_both_roles_see_saved_backend_money_only_and_ignore_client_payment_claims(): void
    {
        foreach (['admin', 'staff'] as $role) {
            [$actor, $table, $order] = $this->fixtures($role);
            $this->withSession(['pos_cart' => ['999' => 99]])->get($this->url($table, $order).'?amount=0.01&status=paid&paid=true')
                ->assertOk()->assertSee($table->table_code)->assertSee($order->order_code)->assertSee('Garden')
                ->assertSee('11.00 VNĐ')->assertSee('2.35 VNĐ')->assertSee('1.00 VNĐ')->assertDontSee('0.01 VNĐ')
                ->assertSee('Thanh toán chưa khả dụng')->assertSee('Chưa xác định')->assertSee('Chưa có bản ghi thanh toán')
                ->assertSee('&lt;b&gt;Saved latte&lt;/b&gt;', false)->assertDontSee('<b>Saved latte</b>', false)
                ->assertSee('&lt;script&gt;note&lt;/script&gt;', false)->assertDontSee('<script>note</script>', false)
                ->assertSee($this->url($table, $order, 'print'));
        }
    }

    public function test_payment_rows_are_reference_only_no_aggregation_receipt_or_sensitive_reference(): void
    {
        [$actor, $table, $order] = $this->fixtures();
        foreach (['paid', 'paid', 'pending', 'failed', 'refunded'] as $index => $status) {
            Payment::forceCreate(['order_id' => $order->id, 'payment_code' => 'PAY'.$index, 'payment_method' => 'cash', 'amount' => '3.00', 'status' => $status, 'created_by' => $actor->id, 'transaction_code' => 'PRIVATE-TRANSACTION']);
        }
        $this->get($this->url($table, $order))->assertOk()->assertSee('PAY0')->assertSee('PAY1')
            ->assertSee('pending')->assertSee('failed')->assertSee('refunded')->assertSee('Bản ghi thanh toán tham chiếu')
            ->assertDontSee('PRIVATE-TRANSACTION')->assertDontSee('name="payment_method"', false)
            ->assertDontSee('Đã thanh toán thành công')->assertViewHas('payments', fn ($payments) => $payments->total() === 5);
        $this->get($this->url($table, $order, 'print'))->assertOk()->assertDontSee('PAY0')->assertDontSee('PRIVATE-TRANSACTION')
            ->assertSee('Không phải biên nhận thanh toán hoặc hóa đơn điện tử.');
    }

    public function test_print_and_refresh_never_write_order_payment_or_table(): void
    {
        [$actor, $table, $order] = $this->fixtures();
        $beforeOrder = $order->fresh()->getAttributes();
        $beforeTable = $table->fresh()->getAttributes();
        DB::enableQueryLog();
        $this->get($this->url($table, $order, 'print'))->assertOk()->assertSee('data-estimate-print', false)->assertSee('Phiếu tạm tính');
        $this->get($this->url($table, $order))->assertOk();
        foreach (DB::getQueryLog() as $entry) {
            $this->assertDoesNotMatchRegularExpression('/^\s*(insert|update|delete)\b/i', $entry['query']);
        }
        DB::disableQueryLog();
        $this->assertSame($beforeOrder, $order->fresh()->getAttributes());
        $this->assertSame($beforeTable, $table->fresh()->getAttributes());
        $this->assertDatabaseCount('payments', 0);
        foreach (['post', 'put', 'patch', 'delete'] as $method) {
            $this->{$method}($this->url($table, $order))->assertStatus(405);
        }
    }

    public function test_ids_actor_and_order_type_are_enforced_for_summary_and_print(): void
    {
        [$actor, $table, $order] = $this->fixtures();
        $other = CafeTable::forceCreate(['area_id' => $table->area_id, 'table_code' => 'OTHER']);
        foreach (['show', 'print'] as $action) {
            $this->get($this->url($other, $order, $action))->assertNotFound();
            $this->get(route('staff.table-checkout.'.$action, [$table->id, 99999]))->assertNotFound();
        }
        $order->forceFill(['order_type' => 'takeaway'])->save();
        $this->get($this->url($table, $order))->assertNotFound();
        $order->forceFill(['order_type' => 'dine_in'])->save();
        $actor->forceFill(['status' => 'inactive'])->save();
        $this->get($this->url($table, $order))->assertForbidden();
        $this->get($this->url($table, $order, 'print'))->assertForbidden();
        auth('web')->logout();
        $this->get($this->url($table, $order))->assertRedirect(route('login'));
        $this->get($this->url($table, $order, 'print'))->assertRedirect(route('login'));
    }

    public function test_cancelled_and_completed_orders_remain_read_only_without_false_payment_claim(): void
    {
        [$actor, $table, $order] = $this->fixtures();
        foreach (['completed', 'cancelled'] as $status) {
            $order->forceFill(['status' => $status])->save();
            $this->get($this->url($table, $order))->assertOk()->assertSee($status)->assertSee('Chưa xác định');
        }
        $this->get($this->url($table, $order, 'print'))->assertOk()->assertSee('Đơn đã hủy — chỉ tham chiếu');
        $order->orderItems()->delete();
        $this->get($this->url($table, $order))->assertOk()->assertSee('Đơn chưa có món đã lưu')->assertDontSee('Xem / in phiếu tạm tính');
    }

    public function test_database_failure_has_unavailable_state_not_fake_zero(): void
    {
        [$actor, $table, $order] = $this->fixtures();
        Log::shouldReceive('warning')->once()->with('Table checkout summary unavailable.', ['sql_state' => null]);
        DB::connection()->beforeExecuting(function ($query): void {
            if (str_contains($query, 'payments')) {
                throw new QueryException('sqlite', 'private SQL', [], new \RuntimeException('private failure'));
            }
        });
        $this->get($this->url($table, $order))->assertOk()->assertSee('Chưa thể tải tạm tính')
            ->assertDontSee('11.00 VNĐ')->assertDontSee('private SQL')->assertViewHas('unavailable', true);
    }

    public function test_payment_history_is_paginated_and_cannot_include_another_orders_payments(): void
    {
        [$actor, $table, $order] = $this->fixtures();
        for ($i = 0; $i < 12; $i++) {
            Payment::forceCreate(['order_id' => $order->id, 'payment_code' => 'PAGE-'.$i, 'payment_method' => 'cash', 'amount' => '1.00', 'status' => 'pending', 'created_by' => $actor->id]);
        }
        $other = $order->replicate();
        $other->order_code = 'OTHER-ORDER';
        $other->save();
        Payment::forceCreate(['order_id' => $other->id, 'payment_code' => 'FOREIGN-PAYMENT', 'payment_method' => 'cash', 'amount' => '1.00', 'status' => 'paid', 'created_by' => $actor->id]);
        $this->get($this->url($table, $order).'?payment_id=13')->assertOk()
            ->assertViewHas('payments', fn ($payments) => $payments->total() === 12 && $payments->count() === 10)
            ->assertDontSee('FOREIGN-PAYMENT')->assertSee('payments_page=2');
        $this->get($this->url($table, $order).'?payments_page=2')->assertOk()
            ->assertViewHas('payments', fn ($payments) => $payments->count() === 2)
            ->assertSee('PAGE-0')->assertSee('PAGE-1')->assertDontSee('FOREIGN-PAYMENT');
    }
}
