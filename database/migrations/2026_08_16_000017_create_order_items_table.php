<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id');
            $table->foreignId('product_id');
            $table->foreignId('variant_id');
            $table->string('product_name');
            $table->string('variant_name', 50)->nullable();
            $table->integer('quantity');
            $table->decimal('unit_price', 12, 2);
            $table->decimal('topping_amount', 12, 2)->default(0);
            $table->decimal('subtotal', 15, 2);
            $table->string('note', 500)->nullable();
            $table->enum('status', ['pending', 'preparing', 'ready', 'served', 'cancelled'])
                ->default('pending');
            $table->timestamps();

            $table->index('order_id', 'order_items_order_id_index');
            $table->index('product_id', 'order_items_product_id_index');
            $table->index('variant_id', 'order_items_variant_id_index');
            $table->index(['status', 'created_at'], 'order_items_status_created_at_index');

            $table->foreign('order_id', 'order_items_order_id_foreign')
                ->references('id')
                ->on('orders')
                ->restrictOnDelete()
                ->noActionOnUpdate();
            $table->foreign('product_id', 'order_items_product_id_foreign')
                ->references('id')
                ->on('products')
                ->restrictOnDelete()
                ->noActionOnUpdate();
            $table->foreign('variant_id', 'order_items_variant_id_foreign')
                ->references('id')
                ->on('product_variants')
                ->restrictOnDelete()
                ->noActionOnUpdate();
        });

        DB::statement(
            'ALTER TABLE order_items
                ADD CONSTRAINT order_items_quantity_positive_check
                    CHECK (quantity > 0),
                ADD CONSTRAINT order_items_unit_price_non_negative_check
                    CHECK (unit_price >= 0),
                ADD CONSTRAINT order_items_topping_amount_non_negative_check
                    CHECK (topping_amount >= 0),
                ADD CONSTRAINT order_items_subtotal_non_negative_check
                    CHECK (subtotal >= 0)'
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
