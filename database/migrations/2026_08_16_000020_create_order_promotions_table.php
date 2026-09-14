<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_promotions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id');
            $table->foreignId('promotion_id');
            $table->string('promotion_code', 50);
            $table->decimal('discount_amount', 15, 2);
            $table->timestamps();

            $table->unique('order_id', 'order_promotions_order_unique');
            $table->index('promotion_id', 'order_promotions_promotion_id_index');

            $table->foreign('order_id', 'order_promotions_order_id_foreign')
                ->references('id')
                ->on('orders')
                ->restrictOnDelete()
                ->noActionOnUpdate();
            $table->foreign('promotion_id', 'order_promotions_promotion_id_foreign')
                ->references('id')
                ->on('promotions')
                ->restrictOnDelete()
                ->noActionOnUpdate();
        });

        DB::statement(
            'ALTER TABLE order_promotions
                ADD CONSTRAINT order_promotions_discount_amount_non_negative_check
                CHECK (discount_amount >= 0)'
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('order_promotions');
    }
};
