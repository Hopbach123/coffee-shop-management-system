<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE orders
                ADD COLUMN delivery_fee DECIMAL(15,2) NOT NULL DEFAULT 0,
                DROP CHECK orders_total_amount_formula_check,
                ADD CONSTRAINT orders_delivery_fee_non_negative_check CHECK (delivery_fee >= 0),
                ADD CONSTRAINT orders_total_amount_formula_check
                    CHECK (total_amount = subtotal - discount_amount + tax_amount + delivery_fee)');

            return;
        }

        Schema::table('orders', function (Blueprint $table): void {
            $table->decimal('delivery_fee', 15, 2)->default(0);
        });
    }

    public function down(): void
    {
        if (DB::table('orders')->where('delivery_fee', '!=', 0)->exists()) {
            throw new RuntimeException('Không rollback phí giao khi còn đơn có phí; cần kế hoạch bảo toàn dữ liệu.');
        }
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE orders
                DROP CHECK orders_total_amount_formula_check,
                DROP CHECK orders_delivery_fee_non_negative_check,
                ADD CONSTRAINT orders_total_amount_formula_check
                    CHECK (total_amount = subtotal - discount_amount + tax_amount),
                DROP COLUMN delivery_fee');

            return;
        }

        Schema::table('orders', function (Blueprint $table): void {
            $table->dropColumn('delivery_fee');
        });
    }
};
