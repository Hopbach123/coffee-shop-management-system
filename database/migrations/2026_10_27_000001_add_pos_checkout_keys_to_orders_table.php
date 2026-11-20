<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->char('pos_checkout_key_hash', 64)->nullable()->unique();
            $table->char('pos_checkout_payload_hash', 64)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->dropUnique(['pos_checkout_key_hash']);
            $table->dropColumn(['pos_checkout_key_hash', 'pos_checkout_payload_hash']);
        });
    }
};
