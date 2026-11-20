<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Nullable fields preserve historical receipts and their original references.
        Schema::table('payments', function (Blueprint $table): void {
            $table->char('operation_key_hash', 64)->nullable()->unique();
            $table->char('operation_payload_hash', 64)->nullable();
            $table->char('bank_reference_hash', 64)->nullable()->unique();
            $table->decimal('received_amount', 15, 2)->nullable();
            $table->decimal('change_amount', 15, 2)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table): void {
            $table->dropUnique(['operation_key_hash']);
            $table->dropUnique(['bank_reference_hash']);
            $table->dropColumn(['operation_key_hash', 'operation_payload_hash', 'bank_reference_hash', 'received_amount', 'change_amount']);
        });
    }
};
