<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\CafeTable;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\Concerns\CreatesTableStatusSources;
use Tests\TestCase;

class TableOrderIntegrationTest extends TestCase
{
    use CreatesTableStatusSources;

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

        (require database_path('migrations/2026_08_14_000002_create_areas_table.php'))->up();
        (require database_path('migrations/2026_08_14_000006_create_cafe_tables_table.php'))->up();
        $this->prepareStatusReadTables();
        $this->withoutVite();
    }

    private function context(string $role = 'admin'): array
    {
        $user = User::forceCreate(['name' => 'Operator', 'email' => uniqid().'@example.test', 'password' => 'password', 'role' => $role, 'status' => 'active']);
        $area = Area::create(['name' => 'Garden']);
        $table = CafeTable::forceCreate(['area_id' => $area->id, 'table_code' => 'B'.uniqid(), 'status' => 'occupied']);
        $order = Order::forceCreate(['table_id' => $table->id, 'user_id' => $user->id, 'order_code' => 'ORD'.uniqid(), 'order_type' => 'dine_in', 'status' => 'pending', 'ordered_at' => now()]);
        $category = Category::create(['name' => 'Coffee', 'description' => null]);
        $product = Product::forceCreate(['category_id' => $category->id, 'name' => 'Latte', 'slug' => uniqid(), 'status' => 'available']);
        $variant = ProductVariant::forceCreate(['product_id' => $product->id, 'name' => 'Large', 'sku' => uniqid(), 'price' => '12.35', 'status' => 'available']);
        $this->actingAs($user, 'web');

        return [$user, $table, $order, $variant, $category];
    }

    private function url($table, $order, string $action = 'index'): string
    {
        return route('staff.table-order.'.$action, [$table->id, $order->id]);
    }

    public function test_admin_and_staff_read_menu_and_save_server_owned_prices_without_new_order_or_status_change(): void
    {
        foreach (['admin', 'staff'] as $role) {
            [$user, $table, $order, $variant] = $this->context($role);
            $this->get($this->url($table, $order))->assertOk()->assertSee('Latte')->assertSee($order->order_code)->assertSee('Món chưa lưu');
            $beforeCount = Order::count();
            $this->postJson($this->url($table, $order, 'store'), ['variant_id' => $variant->id, 'quantity' => 2, 'note' => '<b>less sugar</b>', 'unit_price' => '0.01', 'total_amount' => '0.01'])
                ->assertCreated()->assertJsonPath('snapshot.total_amount', '24.70')->assertJsonPath('snapshot.order_id', $order->id);
            $item = $order->orderItems()->firstOrFail();
            $this->assertSame('12.35', $item->unit_price);
            $this->assertSame(2, $item->quantity);
            $this->assertSame($beforeCount, Order::count());
            $this->assertSame('occupied', $table->fresh()->status);
            $this->getJson($this->url($table, $order))->assertOk()->assertJsonPath('total_amount', '24.70');
        }
    }

    public function test_order_detail_preserves_operations_table_links_and_payment_history_after_integration(): void
    {
        foreach (['admin', 'staff'] as $role) {
            [$user, $table, $order, $variant] = $this->context($role);
            $this->postJson($this->url($table, $order, 'store'), ['variant_id' => $variant->id, 'quantity' => 2])->assertCreated();
            $item = $order->orderItems()->sole();
            $topping = \App\Models\Topping::forceCreate(['name' => 'Extra shot', 'price' => '5.00', 'status' => 'available']);
            $this->post(route('staff.orders.items.toppings.store', [$order, $item]), ['topping_id' => $topping->id, 'quantity' => 1])->assertSessionHasNoErrors();
            $line = $item->orderItemToppings()->sole();
            $payment = \App\Models\Payment::forceCreate([
                'order_id' => $order->id, 'payment_code' => 'PAY-'.$order->id,
                'payment_method' => 'cash', 'amount' => '29.70', 'status' => 'pending',
            ]);

            $this->get(route('staff.orders.show', $order))->assertOk()
                ->assertSee('Garden')->assertSee($payment->payment_code)->assertSee('Extra shot')
                ->assertSee('Hủy đơn')->assertSee('Bắt đầu pha chế')
                ->assertSee($this->url($table, $order))->assertSee(route('staff.table-checkout.show', [$table, $order]))
                ->assertSee('action="'.route('staff.orders.items.store', $order).'"', false)
                ->assertSee('action="'.route('staff.orders.items.update', [$order, $item]).'"', false)
                ->assertSee('action="'.route('staff.orders.items.toppings.update', [$order, $item, $line]).'"', false);

            $order->forceFill(['status' => 'completed', 'completed_at' => now()])->save();
            $this->get(route('staff.orders.show', $order))->assertOk()
                ->assertSee($payment->payment_code)->assertSee('Extra shot')
                ->assertSee(route('staff.table-checkout.show', [$table, $order]))
                ->assertDontSee('Hủy đơn')->assertDontSee('Bắt đầu pha chế')->assertDontSee('Gọi món tại bàn')
                ->assertDontSee('action="'.route('staff.orders.items.store', $order).'"', false);
        }
    }

    public function test_search_category_and_unavailable_products(): void
    {
        [$user, $table, $order, $variant, $category] = $this->context();
        $this->get($this->url($table, $order).'?search=Latte&category_id='.$category->id)->assertOk()->assertViewHas('variants', fn ($variants) => $variants->total() === 1);
        $this->get($this->url($table, $order).'?search=Missing')->assertOk()->assertSee('Không có sản phẩm phù hợp');
        $variant->product->forceFill(['status' => 'unavailable'])->save();
        $this->get($this->url($table, $order))->assertOk()->assertViewHas('variants', fn ($variants) => $variants->isEmpty());
        $this->postJson($this->url($table, $order, 'store'), ['variant_id' => $variant->id, 'quantity' => 1])->assertUnprocessable();
        $this->assertSame(0, $order->orderItems()->count());
    }

    public function test_table_order_mismatch_closed_duplicate_and_maintenance_are_blocked(): void
    {
        [$user, $table, $order, $variant] = $this->context();
        $other = CafeTable::forceCreate(['area_id' => $table->area_id, 'table_code' => 'OTHER']);
        $this->get($this->url($other, $order))->assertNotFound();
        $this->postJson($this->url($other, $order, 'store'), ['variant_id' => $variant->id, 'quantity' => 1])->assertNotFound();
        $order->forceFill(['status' => 'completed'])->save();
        $this->get($this->url($table, $order))->assertStatus(409);
        $order->forceFill(['status' => 'pending'])->save();
        $table->forceFill(['status' => 'maintenance'])->save();
        $this->postJson($this->url($table, $order, 'store'), ['variant_id' => $variant->id, 'quantity' => 1])->assertStatus(409);
        $table->forceFill(['status' => 'occupied'])->save();
        $copy = $order->replicate();
        $copy->order_code = 'DUP';
        $copy->save();
        $this->get($this->url($table, $order))->assertStatus(409);
        $this->postJson($this->url($table, $order, 'store'), ['variant_id' => $variant->id, 'quantity' => 1])->assertStatus(409);
        $this->assertSame(0, $order->orderItems()->count());
    }

    public function test_validation_actor_and_area_are_rechecked(): void
    {
        [$user, $table, $order, $variant] = $this->context();
        $url = $this->url($table, $order, 'store');
        foreach ([0, -1, 1.5, 2147483648] as $quantity) {
            $this->postJson($url, ['variant_id' => $variant->id, 'quantity' => $quantity])->assertUnprocessable();
        }
        $this->postJson($url, ['variant_id' => $variant->id, 'quantity' => 1, 'note' => str_repeat('x', 501)])->assertUnprocessable();
        $this->postJson($url, ['variant_id' => 99999, 'quantity' => 1])->assertUnprocessable();
        $table->area->forceFill(['status' => 'inactive'])->save();
        $this->postJson($url, ['variant_id' => $variant->id, 'quantity' => 1])->assertStatus(409);
        $user->forceFill(['status' => 'inactive'])->save();
        $this->get($this->url($table, $order))->assertForbidden();
        $this->postJson($url, ['variant_id' => $variant->id, 'quantity' => 1])->assertForbidden();
        auth('web')->logout();
        $this->getJson($this->url($table, $order))->assertUnauthorized();
    }

    public function test_price_changes_are_read_at_save_and_failed_second_line_preserves_first(): void
    {
        [$user, $table, $order, $variant] = $this->context();
        $variant->forceFill(['price' => '20.25'])->save();
        $url = $this->url($table, $order, 'store');
        $this->postJson($url, ['variant_id' => $variant->id, 'quantity' => 1])->assertCreated()->assertJsonPath('snapshot.total_amount', '20.25');
        $variant->forceFill(['status' => 'unavailable'])->save();
        $this->postJson($url, ['variant_id' => $variant->id, 'quantity' => 1])->assertUnprocessable();
        $this->assertSame(1, $order->orderItems()->count());
        $this->assertSame('20.25', $order->fresh()->total_amount);
    }

    public function test_detail_resolves_one_order_and_blocks_multiple_and_closed_orders(): void
    {
        [$user, $table, $order] = $this->context();
        $this->get(route('admin.tables.show', $table->id))->assertOk()->assertSee('Tiếp tục gọi món')
            ->assertSee($this->url($table, $order));
        $copy = $order->replicate();
        $copy->order_code = 'CONFLICT';
        $copy->save();
        $this->get(route('admin.tables.show', $table->id))->assertOk()->assertSee('Bàn có nhiều đơn')->assertDontSee('Tiếp tục gọi món');
        $copy->forceFill(['status' => 'cancelled'])->save();
        $order->forceFill(['status' => 'completed'])->save();
        $this->get(route('admin.tables.show', $table->id))->assertOk()->assertSee('Chưa có đơn tại chỗ đang xử lý')->assertDontSee('Tiếp tục gọi món');
    }

    public function test_csrf_is_required_and_sql_failure_returns_no_fake_acknowledgement(): void
    {
        [$user, $table, $order, $variant] = $this->context();
        OrderItem::creating(fn () => throw new QueryException('sqlite', 'private SQL', [], new \RuntimeException('private failure')));
        try {
            $this->postJson($this->url($table, $order, 'store'), ['variant_id' => $variant->id, 'quantity' => 1])
                ->assertStatus(503)->assertJsonMissingPath('saved_item_id')->assertDontSee('private SQL');
            $this->assertSame(0, $order->orderItems()->count());
        } finally {
            OrderItem::flushEventListeners();
        }
        $this->app->instance('env', 'local');
        $this->postJson($this->url($table, $order, 'store'), ['variant_id' => $variant->id, 'quantity' => 1])->assertStatus(419);
    }

    public function test_final_cross_page_flow_persists_then_reads_same_order_without_payment_or_release(): void
    {
        [$actor, $table, $order, $variant] = $this->context();
        $this->get('/admin/areas')->assertOk()->assertViewHas('summary', fn ($s) => (int) $s->occupied_count === 1);
        $this->get('/admin/tables?view=floor&area_id='.$table->area_id)->assertOk()
            ->assertViewHas('tables', fn ($tables) => $tables->pluck('id')->all() === [$table->id]);
        $this->get(route('admin.tables.show', $table->id))->assertOk()->assertSee($this->url($table, $order));
        $this->get($this->url($table, $order))->assertOk()->assertSee('Latte')->assertSee('data-cart-panel', false);
        $this->postJson($this->url($table, $order, 'store'), [
            'variant_id' => $variant->id, 'quantity' => 3, 'note' => '<script>fixture</script>',
            'unit_price' => '0.01', 'total_amount' => '0.01', 'status' => 'paid', 'table_id' => 99999,
        ])->assertCreated()->assertJsonPath('snapshot.order_id', $order->id)->assertJsonPath('snapshot.total_amount', '37.05');
        $this->assertSame('12.35', $order->orderItems()->sole()->unit_price);
        $this->assertSame(3, $order->orderItems()->sole()->quantity);
        $this->assertSame($table->id, $order->fresh()->table_id);
        $this->getJson($this->url($table, $order))->assertOk()->assertJsonPath('total_amount', '37.05')->assertJsonCount(1, 'items');
        $before = [$table->fresh()->getAttributes(), $order->fresh()->getAttributes(), $order->orderItems()->get()->toJson()];
        DB::flushQueryLog();
        DB::enableQueryLog();
        foreach (['show', 'print'] as $action) {
            $this->get(route('staff.table-checkout.'.$action, [$table->id, $order->id]))->assertOk()
                ->assertSee('37.05 VNĐ')->assertSee('&lt;script&gt;fixture&lt;/script&gt;', false)->assertDontSee('<script>fixture</script>', false);
        }
        $this->get('/admin/tables?view=floor&status=occupied')->assertOk()->assertViewHas('summary', fn ($s) => (int) $s->occupied === 1);
        $this->get(route('admin.tables.show', $table->id))->assertOk()->assertViewHas('table', fn ($t) => $t->display_status === 'occupied');
        $this->get('/admin/areas')->assertOk()->assertViewHas('summary', fn ($s) => (int) $s->occupied_count === 1);
        foreach (DB::getQueryLog() as $entry) {
            $this->assertDoesNotMatchRegularExpression('/^\s*(insert|update|delete)\b/i', $entry['query']);
        }
        DB::disableQueryLog();
        $this->assertSame($before, [$table->fresh()->getAttributes(), $order->fresh()->getAttributes(), $order->orderItems()->get()->toJson()]);
        $this->assertDatabaseCount('payments', 0);
        $this->assertDatabaseCount('orders', 1);
    }

    public function test_final_csrf_missing_wrong_and_valid_token_with_actual_web_middleware(): void
    {
        [$actor, $table, $order, $variant] = $this->context('staff');
        $payload = ['variant_id' => $variant->id, 'quantity' => 1];
        // Disable only Laravel's unit-test CSRF shortcut; keep isolated test DB/session.
        $this->app->instance('env', 'local');
        try {
            $this->withSession(['_token' => 'fixture-session-token']);
            $this->postJson($this->url($table, $order, 'store'), $payload)->assertStatus(419);
            $this->postJson($this->url($table, $order, 'store'), $payload, ['X-CSRF-TOKEN' => 'wrong-token'])->assertStatus(419);
            $this->assertDatabaseCount('order_items', 0);
            $this->postJson($this->url($table, $order, 'store'), $payload, ['X-CSRF-TOKEN' => 'fixture-session-token'])->assertCreated();
            $this->assertDatabaseCount('order_items', 1);
        } finally {
            $this->app->instance('env', 'testing');
        }
    }

    public function test_final_existing_create_endpoint_rejects_repeated_create_for_occupied_table(): void
    {
        [$actor, $table] = $this->context('staff');
        $available = CafeTable::forceCreate(['area_id' => $table->area_id, 'table_code' => 'CREATE-FINAL', 'status' => 'available']);
        $payload = ['order_type' => 'dine_in', 'table_id' => $available->id];
        $this->post(route('staff.orders.store'), $payload)->assertRedirect();
        $created = Order::where('table_id', $available->id)->sole();
        $this->assertSame('pending', $created->status);
        $this->assertSame('occupied', $available->fresh()->status);
        $this->postJson(route('staff.orders.store'), $payload)->assertUnprocessable();
        $this->assertSame(1, Order::where('table_id', $available->id)->count());
    }

    public function test_final_read_performance_remains_bounded_for_one_and_twelve_rows(): void
    {
        [$actor, $table, $order, $variant] = $this->context();
        $this->postJson($this->url($table, $order, 'store'), ['variant_id' => $variant->id, 'quantity' => 1])->assertCreated();
        $paths = [
            'areas' => '/admin/areas', 'floor' => '/admin/tables?view=floor',
            'detail' => route('admin.tables.show', $table->id), 'menu' => $this->url($table, $order),
            'checkout' => route('staff.table-checkout.show', [$table->id, $order->id]),
            'estimate' => route('staff.table-checkout.print', [$table->id, $order->id]),
        ];
        $measure = function () use ($paths): array {
            $results = [];
            foreach ($paths as $name => $url) {
                DB::flushQueryLog();
                DB::enableQueryLog();
                $start = hrtime(true);
                $this->get($url)->assertOk();
                $duration = (hrtime(true) - $start) / 1e6;
                $queries = DB::getQueryLog();
                DB::disableQueryLog();
                $fingerprints = array_map(fn ($entry) => hash('sha256', $entry['query'].serialize($entry['bindings'])), $queries);
                $results[$name] = ['queries' => count($queries), 'duplicate_exact_queries' => count($queries) - count(array_unique($fingerprints)), 'db_ms' => round(array_sum(array_column($queries, 'time')), 3), 'http_ms' => round($duration, 3)];
            }

            return $results;
        };
        $small = $measure();
        for ($i = 2; $i <= 12; $i++) {
            CafeTable::forceCreate(['area_id' => $table->area_id, 'table_code' => 'PERF-'.$i, 'status' => 'available']);
            $extra = ProductVariant::forceCreate(['product_id' => $variant->product_id, 'name' => 'Size '.$i, 'price' => '12.35', 'status' => 'available']);
            $this->postJson($this->url($table, $order, 'store'), ['variant_id' => $extra->id, 'quantity' => 1])->assertCreated();
        }
        $large = $measure();
        foreach ($small as $name => $result) {
            $this->assertLessThanOrEqual($result['queries'] + 1, $large[$name]['queries'], $name.' query count grew per row');
        }
        if (getenv('TASK99_EVIDENCE') === '1') {
            file_put_contents(storage_path('logs/task99-performance.json'), json_encode(['environment' => 'sqlite :memory:', 'small' => ['tables' => 1, 'variants' => 1, 'items' => 1, 'measurements' => $small], 'large' => ['tables' => 12, 'variants' => 12, 'items' => 12, 'measurements' => $large]], JSON_PRETTY_PRINT));
        }
    }

    public function test_final_diagnostics_capture_replay_and_reservation_release_without_accepting_them(): void
    {
        [$actor, $table, $order, $variant] = $this->context();
        $payload = ['variant_id' => $variant->id, 'quantity' => 1, 'note' => 'Same request replay'];
        $first = $this->postJson($this->url($table, $order, 'store'), $payload)->assertCreated();
        $second = $this->postJson($this->url($table, $order, 'store'), $payload)->assertCreated();
        $replay = ['first_item' => $first->json('saved_item_id'), 'second_item' => $second->json('saved_item_id'), 'persisted_rows' => $order->orderItems()->count(), 'total' => $order->fresh()->total_amount];
        DB::table('reservations')->insert(['table_id' => $table->id, 'status' => 'confirmed', 'reservation_start_at' => now()->subHour(), 'reservation_end_at' => now()->addHour()]);
        $this->patch(route('staff.orders.cancel', $order->id))->assertRedirect();
        $after = $table->fresh();
        $release = ['order_status' => $order->fresh()->status, 'persisted_table_status' => $after->status, 'live_reservations' => DB::table('reservations')->where('table_id', $table->id)->count()];
        $this->get(route('admin.tables.show', $table->id))->assertOk()->assertViewHas('table', function ($read) use (&$release) {
            $release['display_status'] = $read->display_status;

            return true;
        });
        // These are diagnostic observations, not assertions that replay/release is safe.
        $this->assertSame(1, Order::count());
        $this->assertDatabaseCount('payments', 0);
        if (getenv('TASK99_EVIDENCE') === '1') {
            file_put_contents(storage_path('logs/task99-diagnostics.json'), json_encode(['replay' => $replay, 'cancellation_with_live_reservation' => $release], JSON_PRETTY_PRINT));
        }
    }
}
