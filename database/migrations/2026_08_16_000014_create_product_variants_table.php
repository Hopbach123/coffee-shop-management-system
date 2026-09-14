<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id');
            $table->string('name', 50);
            $table->string('sku', 50)->nullable();
            $table->decimal('price', 12, 2);
            $table->enum('status', ['available', 'unavailable'])->default('available');
            $table->softDeletes();
            $table->timestamps();

            $table->unique('sku', 'product_variants_sku_unique');
            $table->unique(['product_id', 'name'], 'product_variants_product_name_unique');
            $table->foreign('product_id', 'product_variants_product_id_foreign')
                ->references('id')
                ->on('products')
                ->restrictOnDelete()
                ->noActionOnUpdate();
        });

        DB::statement(
            'ALTER TABLE product_variants
                ADD CONSTRAINT product_variants_price_non_negative_check
                CHECK (price >= 0)'
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('product_variants');
    }
};
