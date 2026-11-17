<?php

namespace Tests\Feature;

use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PosCheckoutKeyMigrationTest extends TestCase
{
    public function test_pos_checkout_key_is_unique_and_legacy_orders_can_remain_null(): void
    {
        $this->assertSame('sqlite', DB::connection()->getDriverName());
        $this->assertSame(':memory:', DB::connection()->getDatabaseName());
        Schema::create('orders', function (Blueprint $table): void {
            $table->id();
            $table->string('order_code')->unique();
        });
        $migration = require database_path('migrations/2026_10_27_000001_add_pos_checkout_keys_to_orders_table.php');
        $migration->up();

        DB::table('orders')->insert(['order_code' => 'LEGACY-1']);
        DB::table('orders')->insert(['order_code' => 'LEGACY-2']);
        DB::table('orders')->insert(['order_code' => 'POS-1',
            'pos_checkout_key_hash' => str_repeat('a', 64),
            'pos_checkout_payload_hash' => str_repeat('b', 64)]);

        try {
            DB::table('orders')->insert(['order_code' => 'POS-DUPLICATE',
                'pos_checkout_key_hash' => str_repeat('a', 64),
                'pos_checkout_payload_hash' => str_repeat('c', 64)]);
            $this->fail('Expected the database to reject a duplicate checkout key.');
        } catch (QueryException $exception) {
            $this->assertDatabaseCount('orders', 3);
        }

        $migration->down();
        $this->assertFalse(Schema::hasColumn('orders', 'pos_checkout_key_hash'));
        $this->assertFalse(Schema::hasColumn('orders', 'pos_checkout_payload_hash'));
    }
}
