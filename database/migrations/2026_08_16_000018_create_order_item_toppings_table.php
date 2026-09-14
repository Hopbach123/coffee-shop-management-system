<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_item_toppings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_item_id');
            $table->foreignId('topping_id');
            $table->string('topping_name');
            $table->decimal('price', 12, 2);
            $table->integer('quantity')->default(1);
            $table->timestamps();

            $table->unique(
                ['order_item_id', 'topping_id'],
                'order_item_toppings_item_topping_unique'
            );
            $table->index('topping_id', 'order_item_toppings_topping_id_index');

            $table->foreign('order_item_id', 'order_item_toppings_order_item_id_foreign')
                ->references('id')
                ->on('order_items')
                ->restrictOnDelete()
                ->noActionOnUpdate();
            $table->foreign('topping_id', 'order_item_toppings_topping_id_foreign')
                ->references('id')
                ->on('toppings')
                ->restrictOnDelete()
                ->noActionOnUpdate();
        });

        DB::statement(
            'ALTER TABLE order_item_toppings
                ADD CONSTRAINT order_item_toppings_price_non_negative_check
                    CHECK (price >= 0),
                ADD CONSTRAINT order_item_toppings_quantity_positive_check
                    CHECK (quantity > 0)'
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('order_item_toppings');
    }
};
