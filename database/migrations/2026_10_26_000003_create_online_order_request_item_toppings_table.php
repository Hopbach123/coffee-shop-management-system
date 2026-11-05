<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('online_order_request_item_toppings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('request_item_id')->constrained('online_order_request_items')->restrictOnDelete()->noActionOnUpdate();
            $table->foreignId('topping_id')->constrained('toppings')->restrictOnDelete()->noActionOnUpdate();
            $table->integer('quantity');
            $table->string('topping_name');
            $table->decimal('price', 12, 2);
            $table->timestamps();
            $table->unique(['request_item_id', 'topping_id'], 'online_request_toppings_pair_unique');
        });
        DB::statement('ALTER TABLE online_order_request_item_toppings
            ADD CONSTRAINT online_request_toppings_quantity_check CHECK (quantity > 0),
            ADD CONSTRAINT online_request_toppings_price_check CHECK (price >= 0)');
    }

    public function down(): void
    {
        if (DB::table('online_order_request_item_toppings')->exists()) {
            throw new RuntimeException('Refusing rollback: online request toppings contain business history.');
        }
        Schema::dropIfExists('online_order_request_item_toppings');
    }
};
