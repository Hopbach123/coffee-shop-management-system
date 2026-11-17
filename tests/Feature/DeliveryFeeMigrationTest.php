<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DeliveryFeeMigrationTest extends TestCase
{
    public function test_legacy_orders_default_to_zero_fee_and_rollback_refuses_to_discard_nonzero_fees(): void
    {
        $this->assertSame('sqlite', DB::connection()->getDriverName());
        $this->assertSame(':memory:', DB::connection()->getDatabaseName());
        Schema::create('orders', function (Blueprint $table): void {
            $table->id();
        });
        DB::table('orders')->insert(['id' => 1]);
        $migration = require database_path('migrations/2026_10_27_000002_add_delivery_fee_to_orders_table.php');
        $migration->up();
        $this->assertEquals(0, DB::table('orders')->where('id', 1)->value('delivery_fee'));
        DB::table('orders')->where('id', 1)->update(['delivery_fee' => '20000.00']);
        try {
            $migration->down();
            $this->fail('Rollback must not silently discard historical delivery fees.');
        } catch (\RuntimeException $exception) {
            $this->assertTrue(Schema::hasColumn('orders', 'delivery_fee'));
        }
        DB::table('orders')->where('id', 1)->update(['delivery_fee' => '0.00']);
        $migration->down();
        $this->assertFalse(Schema::hasColumn('orders', 'delivery_fee'));
    }
}
