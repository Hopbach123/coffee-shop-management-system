<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Ingredient;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Topping;
use App\Services\CustomerCartService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

class PublicCustomerCartTest extends TestCase
{
    private Category $category;

    private Product $product;

    private ProductVariant $variant;

    private Topping $topping;

    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame('sqlite', DB::connection()->getDriverName());
        $this->assertSame(':memory:', DB::connection()->getDatabaseName());
        foreach (['2026_08_14_000003_create_categories_table.php',
            '2026_08_14_000004_create_toppings_table.php',
            '2026_08_14_000005_create_ingredients_table.php',
            '2026_08_14_000007_create_products_table.php'] as $migration) {
            (require database_path('migrations/'.$migration))->up();
        }
        // Mirror Variant precision/relations without the production MySQL ALTER CHECK DDL.
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
        foreach (['orders', 'payments', 'inventory_transactions'] as $name) {
            Schema::create($name, fn (Blueprint $table) => $table->id());
        }
        $this->withoutVite();
        $this->category = Category::forceCreate(['name' => 'Cà phê', 'status' => 'active']);
        $this->product = Product::forceCreate(['category_id' => $this->category->id,
            'name' => 'Latte', 'slug' => 'latte', 'status' => 'available']);
        $this->variant = ProductVariant::forceCreate(['product_id' => $this->product->id,
            'name' => 'Lớn', 'price' => '10000.10', 'status' => 'available']);
        $this->topping = Topping::forceCreate(['name' => 'Kem sữa', 'price' => '0.20', 'status' => 'available']);
    }

    private function payload(array $changes = []): array
    {
        return array_replace(['variant_id' => $this->variant->id, 'quantity' => 3,
            'toppings' => [$this->topping->id => 2], 'note' => 'Ít đá'], $changes);
    }

    private function draft(): array
    {
        return session(CustomerCartService::SESSION_KEY, []);
    }

    private function add(array $changes = []): string
    {
        $this->post(route('cart.items.store'), $this->payload($changes))
            ->assertRedirect(route('cart.index'))->assertSessionHasNoErrors();

        return array_key_last($this->draft());
    }

    public function test_guest_has_real_menu_form_cart_navigation_and_empty_cart_without_order_submission(): void
    {
        $this->get(route('menu.show', $this->product))->assertOk()
            ->assertSee(route('cart.items.store'))->assertSee('Thêm vào giỏ hàng')
            ->assertSee('name="variant_id"', false)->assertSee('name="toppings['.$this->topping->id.']"', false);
        $this->get(route('cart.index'))->assertOk()->assertSee('Giỏ hàng đang trống')
            ->assertSee(route('menu.index'))->assertDontSee('Đặt thành công');
        foreach (['cart.index', 'cart.items.store', 'cart.items.update', 'cart.items.destroy'] as $name) {
            $middleware = app('router')->getRoutes()->getByName($name)->gatherMiddleware();
            $this->assertNotContains('auth:web', $middleware);
            $this->assertNotContains('role:admin', $middleware);
            $this->assertNotContains('role:staff', $middleware);
        }
    }

    public function test_guest_adds_server_priced_line_with_independent_topping_quantity(): void
    {
        $id = $this->add();
        $this->assertTrue(Str::isUuid($id));
        $this->assertSame(['variant_id' => $this->variant->id, 'quantity' => 3,
            'toppings' => [$this->topping->id => 2], 'note' => 'Ít đá'], $this->draft()[$id]);
        $quote = app(CustomerCartService::class)->quote($this->draft());
        $this->assertSame('30000.70', $quote['subtotal']);
        $this->assertSame('0,40 ₫', $quote['items'][$id]['topping_amount_label']);
        $this->get(route('cart.index'))->assertOk()->assertSee('30.000,70 ₫')
            ->assertSee('Kem sữa × 2')->assertSee('Ít đá')->assertSee('Giỏ hàng chưa được gửi đến quán');
    }

    public function test_update_changes_only_quantity_and_recalculates_without_scaling_toppings(): void
    {
        $id = $this->add();
        $this->patch(route('cart.items.update', $id), ['quantity' => 2,
            'variant_id' => 999, 'note' => 'malicious', 'toppings' => [], 'subtotal' => '1.00'])
            ->assertRedirect(route('cart.index'))->assertSessionHasNoErrors();
        $this->assertSame(2, $this->draft()[$id]['quantity']);
        $this->assertSame('Ít đá', $this->draft()[$id]['note']);
        $this->assertSame([$this->topping->id => 2], $this->draft()[$id]['toppings']);
        $this->assertSame('20000.60', app(CustomerCartService::class)->quote($this->draft())['subtotal']);
    }

    public function test_distinct_notes_topping_selections_and_repeated_additions_are_preserved_as_separate_lines(): void
    {
        $first = $this->add();
        $second = $this->add(['note' => 'Không đá']);
        $third = $this->add(['toppings' => [], 'note' => 'Ít đá']);
        $fourth = $this->add();
        $this->assertCount(4, $this->draft());
        $this->assertCount(4, array_unique([$first, $second, $third, $fourth]));
        $this->assertSame('Không đá', $this->draft()[$second]['note']);
        $this->assertSame([], $this->draft()[$third]['toppings']);
        $this->assertSame('120002.40', app(CustomerCartService::class)->quote($this->draft())['subtotal']);
    }

    public function test_zero_optional_toppings_are_not_saved_and_note_is_trimmed_and_escaped(): void
    {
        $id = $this->add(['toppings' => [$this->topping->id => 0], 'note' => '  <script>alert(1)</script>  ']);
        $this->assertSame([], $this->draft()[$id]['toppings']);
        $this->get(route('cart.index'))->assertOk()->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)
            ->assertDontSee('<script>alert(1)</script>', false);
        $zeroNote = $this->add(['note' => ' 0 ']);
        $this->assertSame('0', $this->draft()[$zeroNote]['note']);
    }

    public function test_invalid_quantities_and_malformed_topping_keys_do_not_mutate_existing_cart(): void
    {
        $id = $this->add();
        $before = $this->draft();
        foreach ([0, -1, '1.5', 'not-a-number', 2147483648] as $quantity) {
            $this->post(route('cart.items.store'), $this->payload(['quantity' => $quantity]))->assertSessionHasErrors('quantity');
            $this->patch(route('cart.items.update', $id), ['quantity' => $quantity])->assertSessionHasErrors('quantity');
            $this->assertSame($before, $this->draft());
        }
        foreach ([['bad-id' => 1], [$this->topping->id => -1], [$this->topping->id => '1.2']] as $toppings) {
            $this->post(route('cart.items.store'), $this->payload(['toppings' => $toppings]))->assertSessionHasErrors();
            $this->assertSame($before, $this->draft());
        }
        $this->post(route('cart.items.store'), $this->payload(['note' => str_repeat('a', 501)]))->assertSessionHasErrors('note');
        $this->assertSame($before, $this->draft());
    }

    public function test_unavailable_inactive_deleted_and_missing_catalog_records_are_rejected(): void
    {
        foreach ([$this->category, $this->product, $this->variant, $this->topping] as $model) {
            $original = $model->status;
            $model->forceFill(['status' => $model instanceof Category ? 'inactive' : 'unavailable'])->save();
            $this->post(route('cart.items.store'), $this->payload())->assertSessionHasErrors();
            $this->assertSame([], $this->draft());
            $model->forceFill(['status' => $original])->save();
            $model->delete();
            $this->post(route('cart.items.store'), $this->payload())->assertSessionHasErrors();
            $this->assertSame([], $this->draft());
            $model->restore();
        }
        $this->post(route('cart.items.store'), $this->payload(['variant_id' => 999999]))->assertSessionHasErrors();
        $this->post(route('cart.items.store'), $this->payload(['toppings' => [999999 => 1]]))->assertSessionHasErrors();
        $this->assertSame([], $this->draft());
    }

    public function test_cart_requotes_current_prices_read_only_and_keeps_stale_lines_visible_for_removal(): void
    {
        $id = $this->add();
        $before = $this->draft();
        $this->variant->forceFill(['price' => '20000.10'])->save();
        $this->get(route('cart.index'))->assertOk()->assertSee('60.000,70 ₫');
        $this->assertSame($before, $this->draft());
        $this->topping->forceFill(['status' => 'unavailable'])->save();
        $this->get(route('cart.index'))->assertOk()->assertSee('Cần kiểm tra')->assertSee('Chưa thể tính tổng');
        $this->assertSame($before, $this->draft());
        $this->patch(route('cart.items.update', $id), ['quantity' => 2])->assertSessionHasErrors();
        $this->assertSame($before, $this->draft());
        $this->delete(route('cart.items.destroy', $id))->assertRedirect(route('cart.index'));
        $this->assertSame([], $this->draft());
        $this->get(route('cart.index'))->assertOk()->assertSee('Giỏ hàng đang trống');
    }

    public function test_session_carts_are_isolated_and_pos_draft_is_never_modified(): void
    {
        $this->withSession(['pos_cart' => [42 => 7]]);
        $idA = $this->add();
        $draftA = $this->draft();
        $this->assertSame([42 => 7], session('pos_cart'));
        $this->flushSession();
        $this->get(route('cart.index'))->assertOk()->assertSee('Giỏ hàng đang trống');
        $this->patch(route('cart.items.update', $idA), ['quantity' => 2])->assertNotFound();
        $this->delete(route('cart.items.destroy', $idA))->assertNotFound();
        $idB = $this->add(['note' => 'Browser B']);
        $this->assertNotSame($idA, $idB);
        $this->withSession([CustomerCartService::SESSION_KEY => $draftA, 'pos_cart' => [42 => 7]]);
        $this->delete(route('cart.items.destroy', $idB))->assertNotFound();
        $this->assertSame($draftA, $this->draft());
        $this->delete(route('cart.items.destroy', $idA))->assertRedirect(route('cart.index'));
        $this->assertSame([42 => 7], session('pos_cart'));
    }

    public function test_client_money_actor_status_and_names_are_not_stored(): void
    {
        $id = $this->add(['price' => '0.01', 'unit_price' => '0.01', 'subtotal' => '0.01',
            'total_amount' => '0.01', 'status' => 'paid', 'user_id' => 1, 'customer_id' => 1,
            'product_name' => 'Injected', 'order_id' => 1]);
        $this->assertSame(['variant_id', 'quantity', 'toppings', 'note'], array_keys($this->draft()[$id]));
        $this->assertSame('30000.70', app(CustomerCartService::class)->quote($this->draft())['subtotal']);
    }

    public function test_schema_money_and_quantity_overflows_are_rejected_without_losing_cart(): void
    {
        $id = $this->add(['quantity' => 1, 'toppings' => []]);
        $before = $this->draft();
        $this->variant->forceFill(['price' => '9999999999.99'])->save();
        $this->patch(route('cart.items.update', $id), ['quantity' => 1001])->assertSessionHasErrors();
        $this->assertSame($before, $this->draft());
        $this->patch(route('cart.items.update', $id), ['quantity' => 1000])->assertSessionHasNoErrors();
        $this->assertSame('9999999999990.00', app(CustomerCartService::class)->quote($this->draft())['subtotal']);
        $atLimit = $this->draft();
        $this->post(route('cart.items.store'), $this->payload(['quantity' => 1, 'toppings' => []]))->assertSessionHasErrors();
        $this->assertSame($atLimit, $this->draft());
        $this->topping->forceFill(['price' => '9999999999.99'])->save();
        $this->post(route('cart.items.store'), $this->payload())->assertSessionHasErrors();
        $this->assertSame($atLimit, $this->draft());
    }

    public function test_cart_operations_have_no_database_write_or_inventory_order_payment_side_effect(): void
    {
        $ingredient = Ingredient::forceCreate(['name' => 'Hạt', 'unit' => 'g', 'current_stock' => '50.000',
            'minimum_stock' => '0.000', 'cost_price' => '1.00', 'status' => 'active']);
        DB::flushQueryLog();
        DB::enableQueryLog();
        $id = $this->add();
        $this->get(route('cart.index'))->assertOk();
        $this->patch(route('cart.items.update', $id), ['quantity' => 2])->assertSessionHasNoErrors();
        $this->delete(route('cart.items.destroy', $id))->assertRedirect(route('cart.index'));
        $queries = array_column(DB::getQueryLog(), 'query');
        DB::disableQueryLog();
        $this->assertNotEmpty($queries);
        foreach ($queries as $query) {
            $this->assertMatchesRegularExpression('/^select\b/i', trim($query));
        }
        foreach (['orders', 'payments', 'inventory_transactions'] as $table) {
            $this->assertDatabaseCount($table, 0);
        }
        $this->assertSame('50.000', $ingredient->fresh()->current_stock);
    }
}
