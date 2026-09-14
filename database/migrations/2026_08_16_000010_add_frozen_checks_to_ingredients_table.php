<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement(
            'ALTER TABLE ingredients
                ADD CONSTRAINT ingredients_current_stock_non_negative_check
                    CHECK (current_stock >= 0),
                ADD CONSTRAINT ingredients_minimum_stock_non_negative_check
                    CHECK (minimum_stock >= 0),
                ADD CONSTRAINT ingredients_cost_price_non_negative_check
                    CHECK (cost_price >= 0)'
        );
    }

    public function down(): void
    {
        DB::statement(
            'ALTER TABLE ingredients
                DROP CHECK ingredients_current_stock_non_negative_check,
                DROP CHECK ingredients_minimum_stock_non_negative_check,
                DROP CHECK ingredients_cost_price_non_negative_check'
        );
    }
};
