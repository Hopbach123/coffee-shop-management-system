<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement(
            'ALTER TABLE cafe_tables
                DROP FOREIGN KEY cafe_tables_area_id_foreign'
        );

        DB::statement(
            'ALTER TABLE cafe_tables
                ADD CONSTRAINT cafe_tables_area_id_foreign
                FOREIGN KEY (area_id) REFERENCES areas (id)
                ON DELETE RESTRICT ON UPDATE NO ACTION'
        );

        DB::statement(
            'ALTER TABLE cafe_tables
                ADD CONSTRAINT cafe_tables_capacity_positive_check
                CHECK (capacity > 0)'
        );
    }

    public function down(): void
    {
        DB::statement(
            'ALTER TABLE cafe_tables
                DROP CHECK cafe_tables_capacity_positive_check'
        );

        DB::statement(
            'ALTER TABLE cafe_tables
                DROP FOREIGN KEY cafe_tables_area_id_foreign'
        );

        DB::statement(
            'ALTER TABLE cafe_tables
                ADD CONSTRAINT cafe_tables_area_id_foreign
                FOREIGN KEY (area_id) REFERENCES areas (id)'
        );
    }
};
