<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement(
            'ALTER TABLE products
                DROP FOREIGN KEY products_category_id_foreign'
        );

        DB::statement(
            'ALTER TABLE products
                ADD CONSTRAINT products_category_id_foreign
                FOREIGN KEY (category_id) REFERENCES categories (id)
                ON DELETE RESTRICT ON UPDATE NO ACTION'
        );
    }

    public function down(): void
    {
        DB::statement(
            'ALTER TABLE products
                DROP FOREIGN KEY products_category_id_foreign'
        );

        DB::statement(
            'ALTER TABLE products
                ADD CONSTRAINT products_category_id_foreign
                FOREIGN KEY (category_id) REFERENCES categories (id)'
        );
    }
};
