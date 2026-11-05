<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\PickupRequest;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CustomerPickupFlowTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame('sqlite', DB::connection()->getDriverName());
        $this->assertSame(':memory:', DB::connection()->getDatabaseName());
        (require database_path('migrations/2026_08_14_000003_create_categories_table.php'))->up();
        (require database_path('migrations/2026_08_14_000007_create_products_table.php'))->up();
        Schema::create('product_variants', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_id')->constrained('products');
            $table->string('name', 50);
            $table->decimal('price', 12, 2);
            $table->string('status')->default('available');
            $table->softDeletes();
            $table->timestamps();
        });
        (require database_path('migrations/2026_10_13_000001_create_pickup_requests_table.php'))->up();
                        \Illuminate\Support\Facades\Route::middleware('web')->group(function () {
            \Illuminate\Support\Facades\Route::get('/cart', [\App\Http\Controllers\Public\CartController::class, 'index'])->name('cart.index');
            \Illuminate\Support\Facades\Route::post('/cart', [\App\Http\Controllers\Public\CartController::class, 'add'])->name('cart.add');
            \Illuminate\Support\Facades\Route::patch('/cart', [\App\Http\Controllers\Public\CartController::class, 'update'])->name('cart.update');
            \Illuminate\Support\Facades\Route::get('/checkout', [\App\Http\Controllers\Public\CartController::class, 'checkout'])->name('cart.checkout');
            \Illuminate\Support\Facades\Route::post('/checkout', [\App\Http\Controllers\Public\CartController::class, 'place'])->name('cart.place');
            \Illuminate\Support\Facades\Route::get('/checkout/confirmation', [\App\Http\Controllers\Public\CartController::class, 'confirmation'])->name('cart.confirmation');
        });        \Illuminate\Support\Facades\Route::getRoutes()->refreshNameLookups();
        $this->withoutVite();
    }

    private function variant(array $changes = []): ProductVariant
    {
        $category = Category::forceCreate(['name' => 'Cà phê', 'status' => 'active', 'display_order' => 1]);
        $product = Product::forceCreate(['category_id' => $category->id, 'name' => 'Cà phê sữa đá', 'slug' => 'ca-phe-sua-da', 'status' => 'available']);
        return ProductVariant::forceCreate(array_replace(['product_id' => $product->id, 'name' => 'M', 'price' => '35000.00', 'status' => 'available'], $changes));
    }

    public function test_guest_can_add_to_cart_and_prices_are_recalculated_on_server(): void
    {
        $variant = $this->variant();
        $this->post(route('cart.add'), ['variant_id' => $variant->id, 'quantity' => 2, 'unit_price' => '0.01'])
            ->assertRedirect(route('cart.index'));
        $this->get(route('cart.index'))->assertOk()->assertSee('Cà phê sữa đá')->assertSee('70.000đ');
        $this->get(route('cart.checkout'))->assertOk()->assertSee('70.000đ');
        $this->post(route('cart.place'), [
            'customer_name' => 'Nguyễn Văn A', 'customer_phone' => '0901234567',
            'pickup_at' => now()->addHour()->format('Y-m-d H:i:s'),
            'subtotal' => '1.00',
        ])->assertRedirect(route('cart.confirmation'));
        $request = PickupRequest::firstOrFail();
        $this->assertSame('70000.00', $request->subtotal);
        $this->assertSame(2, $request->items[0]['quantity']);
        $this->assertSame('pending', $request->status);
        $this->get(route('cart.confirmation'))->assertOk()->assertSee($request->reference);
        $this->get(route('cart.index'))->assertOk()->assertSee('Giỏ hàng đang trống');
    }

    public function test_unavailable_variant_cannot_be_added_and_checkout_requires_cart(): void
    {
        $variant = $this->variant(['status' => 'unavailable']);
        $this->post(route('cart.add'), ['variant_id' => $variant->id, 'quantity' => 1])->assertSessionHasErrors('cart');
        $this->get(route('cart.checkout'))->assertNotFound();
        $this->assertDatabaseCount('pickup_requests', 0);
    }

    public function test_checkout_rejects_invalid_contact_and_past_pickup_time(): void
    {
        $variant = $this->variant();
        $this->post(route('cart.add'), ['variant_id' => $variant->id, 'quantity' => 1]);
        $this->post(route('cart.place'), ['customer_name' => '', 'customer_phone' => 'bad', 'pickup_at' => now()->subDay()->format('Y-m-d H:i:s')])
            ->assertSessionHasErrors(['customer_name', 'customer_phone', 'pickup_at']);
        $this->assertDatabaseCount('pickup_requests', 0);
    }
}
