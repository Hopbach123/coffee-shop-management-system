<?php

namespace Tests\Feature;

use App\Services\OrderService;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use App\Services\PaymentService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class SalesReceiptWorkflowTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame('sqlite', config('database.default'));
        $this->assertSame(':memory:', DB::connection()->getDatabaseName());
        (require database_path('migrations/0001_01_01_000000_create_users_table.php'))->up();
        Schema::create('orders', function (Blueprint $t) {
            $t->id();
            $t->string('order_code')->unique();
            $t->unsignedBigInteger('user_id');
            $t->string('status')->default('pending');
            $t->string('order_type')->default('takeaway');
            $t->string('purchase_type')->default('counter');
            $t->decimal('total_amount', 15, 2);
            $t->dateTime('ordered_at')->nullable();
            $t->dateTime('completed_at')->nullable();
            $t->timestamps();
        });
        Schema::create('order_items', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('order_id');
            $t->string('status');
            $t->timestamps();
        });
        Schema::create('payments', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('order_id');
            $t->string('payment_code')->unique();
            $t->string('payment_method');
            $t->decimal('amount', 15, 2);
            $t->string('transaction_code')->nullable();
            $t->string('status');
            $t->dateTime('paid_at')->nullable();
            $t->unsignedBigInteger('created_by');
            $t->timestamps();
        });
        (require database_path('migrations/2026_11_17_000001_add_payment_receipt_contract.php'))->up();
    }

    private function fixture(string $type = 'counter'): array
    {
        $actor = User::forceCreate(['name' => 'Cashier', 'email' => uniqid().'@example.test', 'password' => 'test-only', 'role' => 'staff', 'status' => 'active']);
        $order = Order::forceCreate(['order_code' => uniqid('TEST'), 'user_id' => $actor->id, 'purchase_type' => $type, 'order_type' => 'takeaway', 'status' => 'pending', 'total_amount' => '35.50', 'ordered_at' => now()]);
        $item = OrderItem::forceCreate(['order_id' => $order->id, 'status' => 'pending']);

        return [$actor, $order, $item];
    }

    private function payload(array $override = []): array
    {
        return array_replace(['payment_method' => 'cash', 'received_amount' => '50.00', 'received_confirmation' => true, 'operation_key' => bin2hex(random_bytes(16))], $override);
    }

    public function test_cash_receipt_replays_without_preparing_or_completing_and_exposes_change(): void
    {
        [$actor, $order, $item] = $this->fixture();
        $input = $this->payload();
        $service = app(PaymentService::class);
        $first = $service->recordPaid($order, $input, $actor);
        $this->assertSame($first->id, $service->recordPaid($order, $input, $actor)->id);
        $this->assertSame('35.50', $first->amount);
        $this->assertSame('14.50', $first->change_amount);
        $this->assertSame('pending', $item->fresh()->status);
        $this->assertSame('pending', $order->fresh()->status);
        $this->assertDatabaseCount('payments', 1);
        $read = $service->settlementForOrder($order);
        $this->assertSame('paid', $read['payment_status']);
        $this->assertSame('0.00', $read['remaining_amount']);
    }

    public function test_same_key_different_payload_is_rejected(): void
    {
        [$actor, $order] = $this->fixture();
        $input = $this->payload();
        app(PaymentService::class)->recordPaid($order, $input, $actor);
        $this->expectException(ValidationException::class);
        app(PaymentService::class)->recordPaid($order, array_replace($input, ['received_amount' => '60.00']), $actor);
    }

    public function test_second_cashier_or_new_key_cannot_collect_twice(): void
    {
        [$actor, $order] = $this->fixture();
        [$other] = $this->fixture();
        app(PaymentService::class)->recordPaid($order, $this->payload(), $actor);
        try {
            app(PaymentService::class)->recordPaid($order, $this->payload(), $other);
            $this->fail('Duplicate receipt');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('payment', $e->errors());
        }
        $this->assertDatabaseCount('payments', 1);
    }

    public function test_bank_exact_amount_reference_and_no_automatic_scheduled_preparation(): void
    {
        [$actor, $order, $item] = $this->fixture('scheduled');
        $payment = app(PaymentService::class)->recordPaid($order, $this->payload(['payment_method' => 'bank_transfer', 'received_amount' => '35.50', 'transaction_code' => 'bank-A:001']), $actor);
        $this->assertSame('BANK-A:001', $payment->transaction_code);
        $this->assertSame('0.00', $payment->change_amount);
        $this->assertSame('pending', $item->fresh()->status);
        $this->assertSame('pending', $order->fresh()->status);
        [$other, $second] = $this->fixture('online');
        $this->expectException(ValidationException::class);
        app(PaymentService::class)->recordPaid($second, $this->payload(['payment_method' => 'bank_transfer', 'received_amount' => '35.50', 'transaction_code' => ' bank-a:001 ']), $other);
    }

    public function test_bank_wrong_amount_and_cash_short_do_not_write(): void
    {
        [$actor, $order] = $this->fixture();
        foreach ([$this->payload(['received_amount' => '35.49']), $this->payload(['payment_method' => 'bank_transfer', 'received_amount' => '35.51', 'transaction_code' => 'REF123'])] as $input) {
            try {
                app(PaymentService::class)->recordPaid($order, $input, $actor);
                $this->fail('Invalid amount');
            } catch (ValidationException $e) {
                $this->assertArrayHasKey('received_amount', $e->errors());
            }
        }
        $this->assertDatabaseCount('payments', 0);
    }

    public function test_cancelled_or_unauthorized_or_unconfirmed_payment_is_rejected(): void
    {
        [$actor, $order] = $this->fixture();
        $order->forceFill(['status' => 'cancelled'])->save();
        try {
            app(PaymentService::class)->recordPaid($order, $this->payload(), $actor);
            $this->fail();
        } catch (ValidationException) {
            $this->assertDatabaseCount('payments', 0);
        }
        $order->forceFill(['status' => 'pending'])->save();
        $actor->forceFill(['status' => 'inactive'])->save();
        try {
            app(PaymentService::class)->recordPaid($order, $this->payload(), $actor);
            $this->fail();
        } catch (ValidationException) {
            $this->assertDatabaseCount('payments', 0);
        }
        $this->expectException(ValidationException::class);
        app(PaymentService::class)->recordPaid($order, $this->payload(['received_confirmation' => false]), $actor);
    }

    public function test_sales_payment_http_then_handover_then_complete(): void
    {
        [$actor, $order, $item] = $this->fixture('online');
        $item->forceFill(['status' => 'ready'])->save();
        $url = route('staff.orders.payments.store', $order);
        $input = $this->payload(['received_confirmation' => '1']);
        $this->postJson($url, $input)->assertUnauthorized();
        $this->actingAs($actor)->post($url, $input)->assertSessionHasNoErrors();
        $this->post($url, $input)->assertSessionHasNoErrors();
        $this->assertDatabaseCount('payments', 1);
        $this->assertSame('ready', $item->fresh()->status);
        $this->patchJson(route('staff.orders.complete', $order))->assertUnprocessable();
        $this->patch(route('staff.orders.items.status.update', [$order, $item]), ['status' => 'served'])->assertSessionHasNoErrors();
        $this->patch(route('staff.orders.complete', $order))->assertSessionHasNoErrors();
        $this->assertSame('completed', $order->fresh()->status);
    }

    public function test_migration_preserves_legacy_receipt_and_guards_populated_rollback(): void
    {
        [$actor, $order] = $this->fixture();
        app(PaymentService::class)->recordPaid($order, $this->payload(), $actor);
        $this->expectException(\RuntimeException::class);
        (require database_path('migrations/2026_11_17_000001_add_payment_receipt_contract.php'))->down();
    }

    public function test_database_unique_paid_order_and_bank_reference_survive_different_callers(): void
    {
        [$actor, $order] = $this->fixture();
        $paid = app(PaymentService::class)->recordPaid($order, $this->payload(), $actor);
        try {
            $duplicate = $paid->replicate(['paid_order_key']);
            $duplicate->forceFill(['payment_code' => 'OTHER', 'operation_key' => bin2hex(random_bytes(16))])->saveOrFail();
            $this->fail('Unique paid order must reject duplicate insert.');
        } catch (UniqueConstraintViolationException) {
            $this->assertDatabaseCount('payments', 1);
        }
    }

    public function test_receipt_migration_upgrades_and_rolls_back_without_deleting_legacy_data(): void
    {
        $migration = require database_path('migrations/2026_11_17_000001_add_payment_receipt_contract.php');
        $migration->down();
        [$actor, $order] = $this->fixture();
        $id = DB::table('payments')->insertGetId(['order_id' => $order->id, 'payment_code' => 'LEGACY', 'payment_method' => 'bank_transfer', 'amount' => '35.50', 'transaction_code' => 'Legacy-001', 'status' => 'paid', 'created_by' => $actor->id]);
        $migration->up();
        $this->assertSame(hash('sha256', 'LEGACY-001'), DB::table('payments')->where('id', $id)->value('bank_reference_key'));
        $this->assertSame('LEGACY', DB::table('payments')->where('id', $id)->value('payment_code'));
        $migration->down();
        $this->assertFalse(Schema::hasColumn('payments', 'operation_key'));
        $this->assertDatabaseCount('payments', 1);
    }

    public function test_cash_and_bank_support_each_purchase_type_without_mutating_fulfillment(): void
    {
        foreach (['counter', 'online', 'scheduled'] as $type) {
            foreach (['cash', 'bank_transfer'] as $method) {
                [$actor, $order, $item] = $this->fixture($type);
                $receipt = app(OrderService::class)->recordPayment($order, $this->payload([
                    'payment_method' => $method, 'received_amount' => '35.50',
                    'transaction_code' => 'REF-'.$type.'-'.$method,
                ]), $actor);
                $this->assertSame('paid', $receipt->status);
                $this->assertSame('pending', $order->fresh()->status);
                $this->assertSame('pending', $item->fresh()->status);
            }
        }
    }
}
