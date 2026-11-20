<?php

namespace Tests\Concerns;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

trait CreatesTableStatusSources
{
    protected function prepareStatusReadTables(): void
    {
        // Minimal read fixtures when older module tests do not migrate these dependencies.
        if (! Schema::hasTable('orders')) {
            Schema::create('orders', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('table_id')->nullable();
                $table->string('order_type')->default('dine_in');
                $table->string('status')->default('pending');
                $table->string('order_code')->nullable();
                $table->timestamps();
            });
        } elseif (! Schema::hasColumn('orders', 'order_type')) {
            Schema::table('orders', fn (Blueprint $table) => $table->string('order_type')->default('dine_in'));
        }
        if (! Schema::hasTable('reservations')) {
            Schema::create('reservations', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('table_id')->nullable();
                $table->string('status');
                $table->dateTime('reservation_start_at')->nullable();
                $table->dateTime('reservation_end_at')->nullable();
                $table->timestamps();
            });
        } elseif (! Schema::hasColumn('reservations', 'reservation_start_at')) {
            Schema::table('reservations', function (Blueprint $table): void {
                $table->dateTime('reservation_start_at')->nullable();
                $table->dateTime('reservation_end_at')->nullable();
            });
        }
        if (! Schema::hasTable('payments')) {
            Schema::create('payments', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('order_id');
                $table->string('status');
                $table->string('payment_code')->nullable();
                $table->string('payment_method')->nullable();
                $table->decimal('amount', 15, 2)->nullable();
                $table->dateTime('paid_at')->nullable();
                $table->timestamps();
            });
        }
    }
}
