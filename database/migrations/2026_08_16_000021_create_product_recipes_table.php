<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_recipes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('variant_id');
            $table->foreignId('ingredient_id');
            $table->decimal('quantity', 12, 3);
            $table->timestamps();

            $table->unique(
                ['variant_id', 'ingredient_id'],
                'product_recipes_variant_ingredient_unique'
            );
            $table->index('ingredient_id', 'product_recipes_ingredient_id_index');

            $table->foreign('variant_id', 'product_recipes_variant_id_foreign')
                ->references('id')
                ->on('product_variants')
                ->restrictOnDelete()
                ->noActionOnUpdate();
            $table->foreign('ingredient_id', 'product_recipes_ingredient_id_foreign')
                ->references('id')
                ->on('ingredients')
                ->restrictOnDelete()
                ->noActionOnUpdate();
        });

        DB::statement(
            'ALTER TABLE product_recipes
                ADD CONSTRAINT product_recipes_quantity_positive_check
                CHECK (quantity > 0)'
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('product_recipes');
    }
};
