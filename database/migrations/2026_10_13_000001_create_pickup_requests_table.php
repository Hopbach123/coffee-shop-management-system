<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Additive extension for guest pickup requests; no frozen transactional table is changed.
return new class extends Migration {
    public function up(): void
    {
        Schema::create('pickup_requests', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 32)->unique();
            $table->string('customer_name', 120);
            $table->string('customer_phone', 20);
            $table->dateTime('pickup_at');
            $table->json('items');
            $table->decimal('subtotal', 15, 2);
            $table->string('note', 500)->nullable();
            $table->enum('status', ['pending', 'accepted', 'cancelled'])->default('pending');
            $table->timestamps();
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pickup_requests');
    }
};
