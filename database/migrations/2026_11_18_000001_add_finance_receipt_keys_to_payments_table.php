<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Nullable fields preserve historical receipts and their original references.
        Schema::table('payments', function (Blueprint $table): void {
            $table->char('operation_key_hash', 64)->nullable()->unique();
            $table->char('operation_payload_hash', 64)->nullable();
            $table->char('bank_reference_hash', 64)->nullable()->unique();
            if (! Schema::hasColumn('payments', 'received_amount')) {
                $table->decimal('received_amount', 15, 2)->nullable();
            }
            if (! Schema::hasColumn('payments', 'change_amount')) {
                $table->decimal('change_amount', 15, 2)->nullable();
            }
        });
    }

    public function down(): void
    {
        if (DB::table('payments')->whereNotNull('operation_key_hash')->exists()) {
            throw new RuntimeException('Refusing rollback of populated Finance receipt history.');
        }
        Schema::table('payments', function (Blueprint $table): void {
            $table->dropUnique(['operation_key_hash']);
            $table->dropUnique(['bank_reference_hash']);
            $table->dropColumn(['operation_key_hash', 'operation_payload_hash', 'bank_reference_hash']);
            // The earlier Sales migration owns these fields on the integrated schema.
            if (! Schema::hasColumn('payments', 'operation_key')) {
                $table->dropColumn(['received_amount', 'change_amount']);
            }
        });
    }
};
