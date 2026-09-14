<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id');
            $table->string('payment_code', 50);
            $table->enum('payment_method', ['cash', 'bank_transfer']);
            $table->decimal('amount', 15, 2);
            $table->string('transaction_code', 100)->nullable();
            $table->enum('status', ['pending', 'paid', 'failed', 'refunded'])->default('pending');
            $table->dateTime('paid_at')->nullable();
            $table->foreignId('created_by');
            $table->timestamps();

            $table->unique('payment_code', 'payments_payment_code_unique');
            $table->index(['order_id', 'status'], 'payments_order_status_index');
            $table->index('created_by', 'payments_created_by_index');
            $table->index('transaction_code', 'payments_transaction_code_index');

            $table->foreign('order_id', 'payments_order_id_foreign')
                ->references('id')
                ->on('orders')
                ->restrictOnDelete()
                ->noActionOnUpdate();
            $table->foreign('created_by', 'payments_created_by_foreign')
                ->references('id')
                ->on('users')
                ->restrictOnDelete()
                ->noActionOnUpdate();
        });

        DB::statement(
            'ALTER TABLE payments
                ADD CONSTRAINT payments_amount_positive_check
                CHECK (amount > 0)'
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
