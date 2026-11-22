<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Promotion;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class StorefrontAdvisorTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('categories', function (Blueprint $table): void {
            $table->id(); $table->string('name'); $table->string('status')->default('active'); $table->softDeletes(); $table->timestamps();
        });
        Schema::create('products', function (Blueprint $table): void {
            $table->id(); $table->foreignId('category_id'); $table->string('name'); $table->string('slug')->unique(); $table->text('description')->nullable(); $table->string('image')->nullable(); $table->string('status')->default('available'); $table->boolean('is_featured')->default(false); $table->softDeletes(); $table->timestamps();
        });
        Schema::create('product_variants', function (Blueprint $table): void {
            $table->id(); $table->foreignId('product_id'); $table->string('name'); $table->string('sku')->nullable(); $table->decimal('price', 12, 2); $table->string('status')->default('available'); $table->softDeletes(); $table->timestamps();
        });
        Schema::create('promotions', function (Blueprint $table): void {
            $table->id(); $table->string('code')->unique(); $table->string('name'); $table->text('description')->nullable(); $table->string('discount_type'); $table->decimal('discount_value', 12, 2); $table->decimal('max_discount', 12, 2)->nullable(); $table->decimal('minimum_order', 12, 2); $table->dateTime('start_at'); $table->dateTime('end_at'); $table->unsignedInteger('usage_limit')->nullable(); $table->unsignedInteger('used_count')->default(0); $table->string('status')->default('active'); $table->softDeletes(); $table->timestamps();
        });
    }

    public function test_it_returns_only_sellable_records_and_never_mutates_a_cart_or_promotion(): void
    {
        config(['services.gemini.key' => null]);
        $category = Category::forceCreate(['name' => 'Cà phê', 'status' => 'active']);
        $product = Product::forceCreate(['category_id' => $category->id, 'name' => 'Latte', 'slug' => 'latte', 'description' => 'Cà phê sữa', 'status' => 'available']);
        ProductVariant::forceCreate(['product_id' => $product->id, 'name' => 'Nhỏ', 'price' => '30000.00', 'status' => 'available']);
        Product::forceCreate(['category_id' => $category->id, 'name' => 'Ẩn', 'slug' => 'an', 'status' => 'unavailable']);
        $promotion = Promotion::forceCreate(['code' => 'SAVE10', 'name' => 'Giảm 10%', 'discount_type' => 'percentage', 'discount_value' => '10.00', 'minimum_order' => '0.00', 'start_at' => now()->subHour(), 'end_at' => now()->addHour(), 'status' => 'active']);
        Promotion::forceCreate(['code' => 'OLD', 'name' => 'Hết hạn', 'discount_type' => 'fixed', 'discount_value' => '10000.00', 'minimum_order' => '0.00', 'start_at' => now()->subDays(2), 'end_at' => now()->subHour(), 'status' => 'active']);

        $this->postJson(route('storefront-advisor.reply'), ['message' => 'Có khuyến mãi cho cà phê không?'])
            ->assertOk()->assertJsonPath('provider', 'catalog')->assertJsonPath('products.0.name', 'Latte')
            ->assertJsonPath('promotions.0.code', 'SAVE10')->assertJsonMissing(['code' => 'OLD']);

        $this->assertSame(0, Promotion::query()->where('code', 'SAVE10')->value('used_count'));

        config(['services.gemini.key' => 'test-key']);
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response([
            'candidates' => [[
                'content' => [
                    'parts' => [[
                        'text' => json_encode([
                            'reply' => 'Món Latte và ưu đãi SAVE10 phù hợp.',
                            'product_ids' => [$product->id, 999999],
                            'promotion_ids' => [999999, $promotion->id],
                        ]),
                    ]],
                ],
            ]],
        ], 200)]);
        $this->postJson(route('storefront-advisor.reply'), ['message' => 'Gợi ý giúp tôi'])
            ->assertOk()->assertJsonPath('provider', 'gemini')->assertJsonPath('products.0.name', 'Latte')
            ->assertJsonPath('promotions.0.code', 'SAVE10')->assertJsonCount(1, 'products')->assertJsonCount(1, 'promotions');
    }

    public function test_it_validates_message_and_rate_limited_route_is_registered(): void
    {
        $this->postJson(route('storefront-advisor.reply'), ['message' => ''])->assertUnprocessable()->assertJsonValidationErrors('message');
        $route = app('router')->getRoutes()->getByName('storefront-advisor.reply');
        $this->assertNotNull($route);
        $this->assertContains('throttle:storefront-advisor', $route->gatherMiddleware());
    }
}
