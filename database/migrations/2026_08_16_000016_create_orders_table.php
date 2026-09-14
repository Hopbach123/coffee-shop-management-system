<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_code', 50);
            $table->foreignId('table_id')->nullable();
            $table->foreignId('customer_id')->nullable();
            $table->foreignId('user_id');
            $table->foreignId('reservation_id')->nullable();
            $table->enum('order_type', ['dine_in', 'takeaway'])->default('dine_in');
            $table->enum('status', ['pending', 'completed', 'cancelled'])->default('pending');
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->text('note')->nullable();
            $table->dateTime('ordered_at');
            $table->dateTime('completed_at')->nullable();
            $table->timestamps();

            $table->unique('order_code', 'orders_order_code_unique');
            $table->index(['table_id', 'status', 'ordered_at'], 'orders_table_status_ordered_at_index');
            $table->index(['status', 'ordered_at'], 'orders_status_ordered_at_index');
            $table->index('customer_id', 'orders_customer_id_index');
            $table->index('user_id', 'orders_user_id_index');
            $table->index('reservation_id', 'orders_reservation_id_index');

            $table->foreign('table_id', 'orders_table_id_foreign')
                ->references('id')
                ->on('cafe_tables')
                ->restrictOnDelete()
                ->noActionOnUpdate();
            $table->foreign('customer_id', 'orders_customer_id_foreign')
                ->references('id')
                ->on('customers')
                ->restrictOnDelete()
                ->noActionOnUpdate();
            $table->foreign('user_id', 'orders_user_id_foreign')
                ->references('id')
                ->on('users')
                ->restrictOnDelete()
                ->noActionOnUpdate();
            $table->foreign('reservation_id', 'orders_reservation_id_foreign')
                ->references('id')
                ->on('reservations')
                ->restrictOnDelete()
                ->noActionOnUpdate();
        });

        DB::statement(
            'ALTER TABLE orders
                ADD CONSTRAINT orders_subtotal_non_negative_check
                    CHECK (subtotal >= 0),
                ADD CONSTRAINT orders_discount_amount_non_negative_check
                    CHECK (discount_amount >= 0),
                ADD CONSTRAINT orders_tax_amount_non_negative_check
                    CHECK (tax_amount >= 0),
                ADD CONSTRAINT orders_total_amount_non_negative_check
                    CHECK (total_amount >= 0),
                ADD CONSTRAINT orders_discount_not_above_subtotal_check
                    CHECK (discount_amount <= subtotal),
                ADD CONSTRAINT orders_total_amount_formula_check
                    CHECK (total_amount = subtotal - discount_amount + tax_amount)'
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
