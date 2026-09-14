<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ingredient_id');
            $table->enum('type', ['import', 'consume', 'adjustment', 'waste']);
            $table->decimal('quantity', 12, 3);
            $table->decimal('before_quantity', 12, 3);
            $table->decimal('after_quantity', 12, 3);
            $table->string('reference_type', 50)->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->text('note')->nullable();
            $table->foreignId('created_by');
            $table->timestamps();

            $table->index(
                ['ingredient_id', 'created_at'],
                'inventory_transactions_ingredient_created_at_index'
            );
            $table->index('created_by', 'inventory_transactions_created_by_index');
            $table->index(
                ['reference_type', 'reference_id'],
                'inventory_transactions_reference_index'
            );

            $table->foreign('ingredient_id', 'inventory_transactions_ingredient_id_foreign')
                ->references('id')
                ->on('ingredients')
                ->restrictOnDelete()
                ->noActionOnUpdate();
            $table->foreign('created_by', 'inventory_transactions_created_by_foreign')
                ->references('id')
                ->on('users')
                ->restrictOnDelete()
                ->noActionOnUpdate();
        });

        DB::statement(
            'ALTER TABLE inventory_transactions
                ADD CONSTRAINT inventory_transactions_quantity_positive_check
                    CHECK (quantity > 0),
                ADD CONSTRAINT inventory_transactions_before_quantity_non_negative_check
                    CHECK (before_quantity >= 0),
                ADD CONSTRAINT inventory_transactions_after_quantity_non_negative_check
                    CHECK (after_quantity >= 0)'
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_transactions');
    }
};
