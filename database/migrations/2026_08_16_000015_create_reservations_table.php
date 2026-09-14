<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->nullable();
            $table->foreignId('table_id')->nullable();
            $table->string('customer_name');
            $table->string('customer_phone', 20);
            $table->integer('guest_count');
            $table->dateTime('reservation_start_at');
            $table->dateTime('reservation_end_at');
            $table->text('note')->nullable();
            $table->enum('status', ['pending', 'confirmed', 'arrived', 'completed', 'cancelled', 'no_show'])
                ->default('pending');
            $table->foreignId('created_by')->nullable();
            $table->timestamps();

            $table->index(
                ['table_id', 'status', 'reservation_start_at', 'reservation_end_at'],
                'reservations_table_status_window_index'
            );
            $table->index('customer_id', 'reservations_customer_id_index');
            $table->index('created_by', 'reservations_created_by_index');
            $table->index(['status', 'reservation_start_at'], 'reservations_status_start_index');

            $table->foreign('customer_id', 'reservations_customer_id_foreign')
                ->references('id')
                ->on('customers')
                ->restrictOnDelete()
                ->noActionOnUpdate();
            $table->foreign('table_id', 'reservations_table_id_foreign')
                ->references('id')
                ->on('cafe_tables')
                ->restrictOnDelete()
                ->noActionOnUpdate();
            $table->foreign('created_by', 'reservations_created_by_foreign')
                ->references('id')
                ->on('users')
                ->restrictOnDelete()
                ->noActionOnUpdate();
        });

        DB::statement(
            'ALTER TABLE reservations
                ADD CONSTRAINT reservations_guest_count_positive_check
                    CHECK (guest_count > 0),
                ADD CONSTRAINT reservations_valid_date_range_check
                    CHECK (reservation_start_at < reservation_end_at)'
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('reservations');
    }
};
