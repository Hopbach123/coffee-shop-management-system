<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            throw new RuntimeException('Online pickup schema requires MySQL with enforced CHECK constraints.');
        }

        Schema::create('online_order_requests', function (Blueprint $table): void {
            $table->id();
            $table->string('request_code', 50)->unique();
            $table->enum('purchase_type', ['online', 'scheduled']);
            $table->foreignId('customer_id')->nullable()->constrained('customers')->restrictOnDelete()->noActionOnUpdate();
            $table->string('contact_name');
            $table->string('contact_phone', 20);
            $table->dateTime('pickup_at')->nullable();
            $table->string('note', 500)->nullable();
            $table->enum('status', ['pending', 'accepted', 'rejected', 'cancelled'])->default('pending');
            $table->enum('payment_preference', ['cash', 'bank_transfer']);
            $table->enum('payment_timing', ['on_receipt', 'prepaid'])->default('on_receipt');
            $table->foreignId('accepted_by')->nullable()->constrained('users')->restrictOnDelete()->noActionOnUpdate();
            $table->dateTime('accepted_at')->nullable();
            $table->string('rejection_reason', 500)->nullable();
            $table->string('submission_key', 64)->unique();
            $table->char('tracking_token_hash', 64)->unique();
            $table->timestamps();
            $table->index(['status', 'created_at'], 'online_requests_inbox_index');
            $table->index(['purchase_type', 'pickup_at'], 'online_requests_pickup_index');
        });

        DB::statement("ALTER TABLE online_order_requests
            ADD CONSTRAINT online_requests_pickup_check CHECK (
                (purchase_type = 'scheduled' AND pickup_at IS NOT NULL) OR
                (purchase_type = 'online' AND pickup_at IS NULL)),
            ADD CONSTRAINT online_requests_payment_check CHECK (payment_timing <> 'prepaid' OR payment_preference = 'bank_transfer'),
            ADD CONSTRAINT online_requests_acceptance_check CHECK (
                (status IN ('pending','rejected') AND accepted_by IS NULL AND accepted_at IS NULL) OR
                (status = 'accepted' AND accepted_by IS NOT NULL AND accepted_at IS NOT NULL) OR
                (status = 'cancelled' AND ((accepted_by IS NULL AND accepted_at IS NULL) OR (accepted_by IS NOT NULL AND accepted_at IS NOT NULL)))),
            ADD CONSTRAINT online_requests_rejection_check CHECK (status <> 'rejected' OR (rejection_reason IS NOT NULL AND CHAR_LENGTH(TRIM(rejection_reason)) > 0)),
            ADD CONSTRAINT online_requests_required_text_check CHECK (
                CHAR_LENGTH(TRIM(request_code)) > 0 AND CHAR_LENGTH(TRIM(contact_name)) > 0 AND
                CHAR_LENGTH(TRIM(contact_phone)) > 0 AND CHAR_LENGTH(TRIM(submission_key)) > 0),
            ADD CONSTRAINT online_requests_token_check CHECK (REGEXP_LIKE(tracking_token_hash, '^[0-9a-fA-F]{64}$', 'c'))
        ");
    }

    public function down(): void
    {
        if (DB::table('online_order_requests')->exists()) {
            throw new RuntimeException('Refusing rollback: online order requests contain business history.');
        }
        Schema::dropIfExists('online_order_requests');
    }
};
