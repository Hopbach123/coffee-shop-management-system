<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('online_order_request_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('request_id')->constrained('online_order_requests')->restrictOnDelete()->noActionOnUpdate();
            $table->foreignId('variant_id')->constrained('product_variants')->restrictOnDelete()->noActionOnUpdate();
            $table->integer('quantity');
            $table->string('note', 500)->nullable();
            $table->string('product_name');
            $table->string('variant_name', 50);
            $table->decimal('unit_price', 12, 2);
            $table->timestamps();
        });
        DB::statement('ALTER TABLE online_order_request_items
            ADD CONSTRAINT online_request_items_quantity_check CHECK (quantity > 0),
            ADD CONSTRAINT online_request_items_price_check CHECK (unit_price >= 0)');
    }

    public function down(): void
    {
        if (DB::table('online_order_request_items')->exists()) {
            throw new RuntimeException('Refusing rollback: online request items contain business history.');
        }
        Schema::dropIfExists('online_order_request_items');
    }
};
