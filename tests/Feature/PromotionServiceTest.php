<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Promotion;
use App\Models\User;
use App\Services\PromotionService;
use DomainException;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PromotionServiceTest extends TestCase
{
    private PromotionService $service;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame('sqlite', DB::connection()->getDriverName());
        $this->assertSame(':memory:', DB::connection()->getDatabaseName());
        (require database_path('migrations/0001_01_01_000000_create_users_table.php'))->up();
        (require database_path('migrations/2026_08_14_000008_create_promotions_table.php'))->up();
        Schema::create('orders', function (Blueprint $table): void {
            $table->id();
            $table->string('order_code')->unique();
            $table->unsignedBigInteger('user_id');
            $table->string('status')->default('pending');
            $table->decimal('subtotal', 15, 2);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2);
            $table->dateTime('ordered_at');
            $table->timestamps();
        });
        Schema::create('order_promotions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('order_id')->unique();
            $table->unsignedBigInteger('promotion_id');
            $table->string('promotion_code', 50);
            $table->decimal('discount_amount', 15, 2);
            $table->timestamps();
        });
        Schema::create('payments', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->string('status');
            $table->dateTime('paid_at')->nullable();
            $table->timestamps();
        });
        $this->travelTo('2026-06-01 12:00:00');
        $this->user = User::forceCreate([
            'name' => 'Service test user', 'email' => 'service@example.test',
            'password' => 'test-password', 'role' => 'staff', 'status' => 'active',
        ]);
        $this->service = new PromotionService;
    }

    protected function tearDown(): void
    {
        $this->travelBack();
        parent::tearDown();
    }

    private function order(array $changes = []): Order
    {
        return Order::forceCreate(array_replace([
            'order_code' => 'O'.uniqid(), 'user_id' => $this->user->id,
            'status' => 'pending', 'subtotal' => '100000.00',
            'discount_amount' => '0.00', 'tax_amount' => '5000.00',
            'total_amount' => '105000.00', 'ordered_at' => now(),
        ], $changes));
    }

    private function promotion(array $changes = []): Promotion
    {
        return Promotion::forceCreate(array_replace([
            'code' => 'CAFE10', 'name' => 'Coffee discount',
            'discount_type' => 'percentage', 'discount_value' => '10.00',
            'max_discount' => null, 'minimum_order' => '0.00',
            'start_at' => '2026-06-01 11:00:00', 'end_at' => '2026-06-01 13:00:00',
            'usage_limit' => null, 'used_count' => 0, 'status' => 'active',
        ], $changes));
    }

    public function test_inclusive_dates_code_lookup_and_preview_do_not_write(): void
    {
        $promotion = $this->promotion();
        $order = $this->order();
        $this->travelTo('2026-06-01 11:00:00');
        $this->assertSame('10000.00', $this->service->previewDiscount($order, ' cafe10 '));
        $this->travelTo('2026-06-01 13:00:00');
        $this->assertSame('10000.00', $this->service->previewDiscount($order, 'Cafe10'));
        $this->assertDatabaseCount('order_promotions', 0);
        $this->assertSame(0, $promotion->fresh()->used_count);
    }

    public function test_rejects_each_ineligible_condition(): void
    {
        $order = $this->order();
        $promotion = $this->promotion();
        $cases = [
            ['status' => 'inactive'],
            ['start_at' => '2026-06-02 00:00:00'],
            ['end_at' => '2026-05-31 23:59:59'],
            ['minimum_order' => '100000.01'],
            ['usage_limit' => 1, 'used_count' => 1],
        ];
        foreach ($cases as $changes) {
            $promotion->forceFill(array_replace([
                'status' => 'active', 'start_at' => '2026-06-01 11:00:00',
                'end_at' => '2026-06-01 13:00:00', 'minimum_order' => '0.00',
                'usage_limit' => null, 'used_count' => 0,
            ], $changes))->save();
            try {
                $this->service->applyToOrder($order, 'CAFE10');
                $this->fail('Ineligible promotion was accepted.');
            } catch (DomainException) {
                $this->assertDatabaseCount('order_promotions', 0);
            }
        }
        $promotion->delete();
        $this->expectException(DomainException::class);
        $this->service->previewDiscount($order, 'CAFE10');
    }

    public function test_missing_code_and_invalid_order_state_fail(): void
    {
        $order = $this->order();
        try {
            $this->service->previewDiscount($order, 'NOPE');
            $this->fail('Missing code was accepted.');
        } catch (DomainException $error) {
            $this->assertStringContainsString('không tồn tại', $error->getMessage());
        }
        $this->promotion();
        $order->status = 'completed';
        $order->save();
        $this->expectException(DomainException::class);
        $this->service->applyToOrder($order, 'CAFE10');
    }

    public function test_fixed_percentage_caps_and_cent_precision(): void
    {
        $promotion = $this->promotion(['discount_type' => 'fixed', 'discount_value' => '5000.00']);
        $this->assertSame('5000.00', $this->service->calculateDiscount($promotion, '10000.00'));
        $promotion->discount_value = '15000.00';
        $this->assertSame('10000.00', $this->service->calculateDiscount($promotion, '10000.00'));
        $promotion->discount_type = 'percentage';
        $promotion->discount_value = '12.50';
        $promotion->max_discount = null;
        $this->assertSame('12.50', $this->service->calculateDiscount($promotion, '100.00'));
        $this->assertSame('0.01', $this->service->calculateDiscount($promotion, '0.05'));
        $promotion->max_discount = '10.00';
        $this->assertSame('10.00', $this->service->calculateDiscount($promotion, '100.00'));
        $promotion->discount_value = '150.00';
        $promotion->max_discount = null;
        $this->assertSame('100.00', $this->service->calculateDiscount($promotion, '100.00'));
    }

    public function test_invalid_discount_configuration_fails_clearly(): void
    {
        $promotion = $this->promotion();
        $promotion->discount_type = 'unknown';
        $this->expectException(DomainException::class);
        $this->service->calculateDiscount($promotion, '100.00');
    }

    public function test_apply_snapshots_and_updates_totals_without_consuming(): void
    {
        $order = $this->order();
        $promotion = $this->promotion(['discount_type' => 'fixed', 'discount_value' => '20000.00']);
        $snapshot = $this->service->applyToOrder($order, ' cafe10 ');
        $this->assertSame($promotion->id, $snapshot->promotion_id);
        $this->assertSame('CAFE10', $snapshot->promotion_code);
        $this->assertSame('20000.00', $snapshot->discount_amount);
        $this->assertSame('20000.00', $order->fresh()->discount_amount);
        $this->assertSame('85000.00', $order->fresh()->total_amount);
        $this->assertSame(0, $promotion->fresh()->used_count);
        $promotion->code = 'NEWCODE';
        $promotion->save();
        $this->assertSame('CAFE10', $snapshot->fresh()->promotion_code);
        $this->expectException(DomainException::class);
        $this->service->applyToOrder($order, 'NEWCODE');
    }

    public function test_apply_rolls_back_snapshot_if_order_update_fails(): void
    {
        $order = $this->order();
        $this->promotion();
        DB::statement("CREATE TRIGGER reject_order_change BEFORE UPDATE ON orders BEGIN SELECT RAISE(FAIL, 'forced failure'); END");
        try {
            $this->service->applyToOrder($order, 'CAFE10');
            $this->fail('Expected SQL failure.');
        } catch (QueryException) {
            $this->assertDatabaseCount('order_promotions', 0);
            $this->assertSame('0.00', $order->fresh()->discount_amount);
            $this->assertSame('105000.00', $order->fresh()->total_amount);
        }
    }

    public function test_payment_usage_contract_is_idempotent_and_checks_quota(): void
    {
        $promotion = $this->promotion(['usage_limit' => 1]);
        $firstOrder = $this->order();
        $secondOrder = $this->order();
        $this->service->applyToOrder($firstOrder, 'CAFE10');
        $this->service->applyToOrder($secondOrder, 'CAFE10');
        $this->assertSame(0, $promotion->fresh()->used_count);

        try {
            $this->service->consumeUsageAfterPaid($firstOrder);
            $this->fail('Usage was consumed outside the payment transaction.');
        } catch (DomainException) {
            $this->assertSame(0, $promotion->fresh()->used_count);
        }
        try {
            DB::transaction(fn () => $this->service->consumeUsageAfterPaid($firstOrder));
            $this->fail('Usage was consumed without a paid payment.');
        } catch (DomainException) {
            $this->assertSame(0, $promotion->fresh()->used_count);
        }

        $this->assertFalse(DB::transaction(fn () => $this->service->consumeUsageAfterPaid($this->order())));
        $this->assertTrue(DB::transaction(function () use ($firstOrder): bool {
            $this->service->lockPromotionForPayment($firstOrder);
            DB::table('payments')->insert(['order_id' => $firstOrder->id, 'status' => 'paid', 'paid_at' => now()]);

            return $this->service->consumeUsageAfterPaid($firstOrder);
        }));
        $this->assertFalse(DB::transaction(fn () => $this->service->consumeUsageAfterPaid($firstOrder)));
        $this->assertSame(1, $promotion->fresh()->used_count);

        try {
            DB::transaction(function () use ($secondOrder): void {
                $this->service->lockPromotionForPayment($secondOrder);
                DB::table('payments')->insert(['order_id' => $secondOrder->id, 'status' => 'paid', 'paid_at' => now()]);
                $this->service->consumeUsageAfterPaid($secondOrder);
            });
            $this->fail('Quota overrun was accepted.');
        } catch (DomainException) {
            $this->assertDatabaseCount('payments', 1);
            $this->assertSame(1, $promotion->fresh()->used_count);
        }
    }

    public function test_unlimited_promotion_counts_each_paid_order_once(): void
    {
        $promotion = $this->promotion();
        foreach ([$this->order(), $this->order()] as $order) {
            $this->service->applyToOrder($order, 'CAFE10');
            DB::transaction(function () use ($order): void {
                $this->service->lockPromotionForPayment($order);
                DB::table('payments')->insert(['order_id' => $order->id, 'status' => 'paid', 'paid_at' => now()]);
                $this->assertTrue($this->service->consumeUsageAfterPaid($order));
            });
        }
        $this->assertSame(2, $promotion->fresh()->used_count);
    }
}
