<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::table('payments')->where('status', 'paid')->select('order_id')->groupBy('order_id')->havingRaw('COUNT(*) > 1')->exists()) {
            throw new RuntimeException('Duplicate paid receipts require review before migration.');
        }
        $bankKeys = [];
        foreach (DB::table('payments')->where('payment_method', 'bank_transfer')->whereNotNull('transaction_code')->get(['id', 'transaction_code']) as $row) {
            $reference = strtoupper(trim($row->transaction_code));
            if ($reference === '') {
                continue;
            }
            $key = hash('sha256', $reference);
            if (isset($bankKeys[$key])) {
                throw new RuntimeException('Duplicate legacy bank references require review before migration.');
            }
            $bankKeys[$key] = $row->id;
        }
        Schema::table('payments', function (Blueprint $table): void {
            $table->string('operation_key', 64)->nullable()->unique();
            $table->char('payload_hash', 64)->nullable();
            // Finance may already be deployed before this Sales branch is integrated.
            if (! Schema::hasColumn('payments', 'received_amount')) {
                $table->decimal('received_amount', 15, 2)->nullable();
            }
            if (! Schema::hasColumn('payments', 'change_amount')) {
                $table->decimal('change_amount', 15, 2)->nullable();
            }
            // Canonical bank reference is reserved permanently, including failed/refunded history.
            $table->char('bank_reference_key', 64)->nullable()->unique();
            $table->unsignedBigInteger('paid_order_key')->nullable()
                ->virtualAs("CASE WHEN status = 'paid' THEN order_id ELSE NULL END")->unique();
        });
        foreach ($bankKeys as $key => $id) {
            DB::table('payments')->where('id', $id)->update(['bank_reference_key' => $key]);
        }
    }

    public function down(): void
    {
        if (DB::table('payments')->whereNotNull('operation_key')->exists()) {
            throw new RuntimeException('Refusing rollback of populated receipt history.');
        }
        Schema::table('payments', function (Blueprint $table): void {
            $table->dropUnique(['operation_key']);
            $table->dropUnique(['bank_reference_key']);
            $table->dropUnique(['paid_order_key']);
            $table->dropColumn(['operation_key', 'payload_hash', 'bank_reference_key', 'paid_order_key']);
            if (! Schema::hasColumn('payments', 'operation_key_hash')) {
                $table->dropColumn(['received_amount', 'change_amount']);
            }
        });
    }
};
