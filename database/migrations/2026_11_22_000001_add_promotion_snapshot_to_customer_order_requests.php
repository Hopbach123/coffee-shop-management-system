<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customer_order_requests', function (Blueprint $table): void {
            $table->string('promotion_code', 50)->nullable()->after('payment_option');
            $table->decimal('promotion_discount', 15, 2)->default(0)->after('promotion_code');
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE customer_order_requests DROP CHECK cor_amounts_check');
            DB::statement('ALTER TABLE customer_order_requests
                ADD CONSTRAINT cor_amounts_check
                CHECK (quoted_subtotal >= 0 AND promotion_discount >= 0 AND delivery_fee >= 0
                    AND quoted_total = quoted_subtotal - promotion_discount + delivery_fee)');
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE customer_order_requests DROP CHECK cor_amounts_check');
        }

        Schema::table('customer_order_requests', function (Blueprint $table): void {
            $table->dropColumn(['promotion_code', 'promotion_discount']);
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE customer_order_requests
                ADD CONSTRAINT cor_amounts_check
                CHECK (quoted_subtotal >= 0 AND delivery_fee >= 0 AND quoted_total = quoted_subtotal + delivery_fee)');
        }
    }
};
