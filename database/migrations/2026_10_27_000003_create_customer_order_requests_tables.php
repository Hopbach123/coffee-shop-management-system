<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_order_requests', function (Blueprint $table): void {
            $table->id();
            $table->char('reference', 26)->unique('cor_reference_unique');
            $table->enum('status', ['pending', 'accepted', 'rejected'])->default('pending');
            $table->string('customer_name');
            $table->string('customer_phone', 20);
            $table->string('customer_address', 500);
            $table->string('note', 500)->nullable();
            $table->enum('payment_option', ['bank_transfer_prepaid', 'cod']);
            $table->decimal('quoted_subtotal', 15, 2);
            $table->decimal('delivery_fee', 15, 2);
            $table->decimal('quoted_total', 15, 2);
            $table->char('submission_key_hash', 64)->unique('cor_submission_key_unique');
            $table->char('payload_hash', 64);
            $table->char('access_token_hash', 64)->unique('cor_access_token_unique');
            $table->dateTime('access_expires_at');
            $table->foreignId('order_id')->nullable()->unique('cor_order_unique')->constrained('orders')->restrictOnDelete();
            $table->foreignId('handled_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->dateTime('handled_at')->nullable();
            $table->dateTime('dispatched_at')->nullable();
            $table->dateTime('delivered_at')->nullable();
            $table->string('rejection_note', 500)->nullable();
            $table->timestamps();
            $table->index(['status', 'created_at'], 'cor_status_created_index');
        });
        Schema::create('customer_order_request_items', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('request_id');
            $table->foreign('request_id', 'cori_request_fk')->references('id')->on('customer_order_requests')->restrictOnDelete();
            $table->unsignedBigInteger('variant_id');
            $table->foreign('variant_id', 'cori_variant_fk')->references('id')->on('product_variants')->restrictOnDelete();
            $table->string('product_name');
            $table->string('variant_name', 50)->nullable();
            $table->unsignedInteger('quantity');
            $table->decimal('unit_price', 12, 2);
            $table->decimal('topping_amount', 12, 2);
            $table->decimal('subtotal', 15, 2);
            $table->string('note', 500)->nullable();
            $table->timestamps();
        });
        Schema::create('customer_order_request_item_toppings', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('request_item_id');
            $table->foreign('request_item_id', 'corit_item_fk')->references('id')->on('customer_order_request_items')->restrictOnDelete();
            $table->unsignedBigInteger('topping_id');
            $table->foreign('topping_id', 'corit_topping_fk')->references('id')->on('toppings')->restrictOnDelete();
            $table->string('topping_name');
            $table->decimal('price', 12, 2);
            $table->unsignedInteger('quantity');
            $table->timestamps();
            $table->unique(['request_item_id', 'topping_id'], 'corit_item_topping_unique');
        });
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE customer_order_requests
                ADD CONSTRAINT cor_amounts_check CHECK (quoted_subtotal >= 0 AND delivery_fee >= 0 AND quoted_total = quoted_subtotal + delivery_fee)');
            DB::statement('ALTER TABLE customer_order_request_items
                ADD CONSTRAINT cori_amounts_check CHECK (quantity > 0 AND unit_price >= 0 AND topping_amount >= 0 AND subtotal = unit_price * quantity + topping_amount)');
            DB::statement('ALTER TABLE customer_order_request_item_toppings
                ADD CONSTRAINT corit_amounts_check CHECK (quantity > 0 AND price >= 0)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_order_request_item_toppings');
        Schema::dropIfExists('customer_order_request_items');
        Schema::dropIfExists('customer_order_requests');
    }
};
