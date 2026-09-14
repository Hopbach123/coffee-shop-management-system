<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cafe_tables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('area_id')->constrained('areas');
            $table->string('table_code', 20)->unique();
            $table->string('table_name', 100)->nullable();
            $table->integer('capacity')->default(4);
            $table->enum('status', ['available', 'occupied', 'maintenance'])->default('available');
            $table->string('qr_code')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cafe_tables');
    }
};
