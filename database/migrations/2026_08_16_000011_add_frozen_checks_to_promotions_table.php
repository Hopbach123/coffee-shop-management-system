<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement(
            'ALTER TABLE promotions
                ADD CONSTRAINT promotions_discount_value_non_negative_check
                    CHECK (discount_value >= 0),
                ADD CONSTRAINT promotions_max_discount_non_negative_check
                    CHECK (max_discount IS NULL OR max_discount >= 0),
                ADD CONSTRAINT promotions_minimum_order_non_negative_check
                    CHECK (minimum_order >= 0),
                ADD CONSTRAINT promotions_usage_limit_non_negative_check
                    CHECK (usage_limit IS NULL OR usage_limit >= 0),
                ADD CONSTRAINT promotions_used_count_non_negative_check
                    CHECK (used_count >= 0),
                ADD CONSTRAINT promotions_valid_date_range_check
                    CHECK (start_at < end_at)'
        );
    }

    public function down(): void
    {
        DB::statement(
            'ALTER TABLE promotions
                DROP CHECK promotions_discount_value_non_negative_check,
                DROP CHECK promotions_max_discount_non_negative_check,
                DROP CHECK promotions_minimum_order_non_negative_check,
                DROP CHECK promotions_usage_limit_non_negative_check,
                DROP CHECK promotions_used_count_non_negative_check,
                DROP CHECK promotions_valid_date_range_check'
        );
    }
};
