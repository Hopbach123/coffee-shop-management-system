<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // MySQL forbids CHECK expressions referencing AUTO_INCREMENT; use triggers there.
        $driver = DB::connection()->getDriverName();
        if (! in_array($driver, ['mysql', 'sqlite'], true)) {
            throw new RuntimeException('Admin menu migration supports MySQL and SQLite only.');
        }
        $primaryKey = $driver === 'mysql'
            ? 'BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY'
            : 'INTEGER PRIMARY KEY AUTOINCREMENT';
        $parentCheck = $driver === 'sqlite'
            ? 'CONSTRAINT admin_menus_parent_check CHECK (parent_id IS NULL OR parent_id <> id),'
            : '';
        DB::statement("CREATE TABLE admin_menus (
            id {$primaryKey},
            parent_id BIGINT UNSIGNED NULL,
            name VARCHAR(100) NOT NULL,
            type VARCHAR(20) NOT NULL,
            route_name VARCHAR(150) NULL,
            icon VARCHAR(100) NULL,
            sort_order INTEGER UNSIGNED NOT NULL DEFAULT 0,
            is_active BOOLEAN NOT NULL DEFAULT 1,
            permission_key VARCHAR(150) NULL,
            created_at TIMESTAMP NULL,
            updated_at TIMESTAMP NULL,
            CONSTRAINT admin_menus_parent_fk FOREIGN KEY (parent_id)
                REFERENCES admin_menus(id) ON DELETE RESTRICT ON UPDATE RESTRICT,
            CONSTRAINT admin_menus_type_check CHECK (type IN ('group', 'link')),
            {$parentCheck}
            CONSTRAINT admin_menus_route_check CHECK (
                (type = 'group' AND route_name IS NULL) OR
                (type = 'link' AND route_name IS NOT NULL AND TRIM(route_name) <> '')
            ),
            CONSTRAINT admin_menus_sort_check CHECK (sort_order >= 0 AND sort_order <= 4294967295)
        )");
        Schema::table('admin_menus', function (Blueprint $table): void {
            $table->index(['parent_id', 'sort_order', 'id'], 'admin_menus_siblings_index');
        });
        if ($driver === 'mysql') {
            // AFTER INSERT sees the assigned auto-increment value, unlike BEFORE INSERT.
            DB::unprepared("CREATE TRIGGER admin_menus_no_self_insert AFTER INSERT ON admin_menus
                FOR EACH ROW BEGIN
                    IF NEW.parent_id = NEW.id THEN
                        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Menu cannot be its own parent';
                    END IF;
                END");
            DB::unprepared("CREATE TRIGGER admin_menus_no_self_update BEFORE UPDATE ON admin_menus
                FOR EACH ROW BEGIN
                    IF NEW.parent_id = NEW.id THEN
                        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Menu cannot be its own parent';
                    END IF;
                END");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_menus');
    }
};
