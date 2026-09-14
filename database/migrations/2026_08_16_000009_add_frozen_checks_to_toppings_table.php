<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement(
            'ALTER TABLE toppings
                ADD CONSTRAINT toppings_price_non_negative_check
                CHECK (price >= 0)'
        );
    }

    public function down(): void
    {
        DB::statement(
            'ALTER TABLE toppings
                DROP CHECK toppings_price_non_negative_check'
        );
    }
};
