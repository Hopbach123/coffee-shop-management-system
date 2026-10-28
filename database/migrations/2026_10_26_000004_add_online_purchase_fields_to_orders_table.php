<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // One ALTER avoids a partially-added column if a constraint cannot be created.
        DB::statement("ALTER TABLE orders
            ADD COLUMN purchase_type ENUM('counter','scheduled','online') NOT NULL DEFAULT 'counter',
            ADD COLUMN online_request_id BIGINT UNSIGNED NULL,
            ADD UNIQUE KEY orders_online_request_unique (online_request_id),
            ADD KEY orders_purchase_status_time_index (purchase_type,status,ordered_at),
            ADD CONSTRAINT orders_online_request_foreign FOREIGN KEY (online_request_id)
                REFERENCES online_order_requests(id) ON DELETE RESTRICT ON UPDATE NO ACTION,
            ADD CONSTRAINT orders_purchase_link_check CHECK (
                (purchase_type = 'counter' AND online_request_id IS NULL) OR
                (purchase_type IN ('scheduled','online') AND online_request_id IS NOT NULL AND order_type = 'takeaway' AND table_id IS NULL))
        ");
    }

    public function down(): void
    {
        if (DB::table('orders')->where('purchase_type', '<>', 'counter')->orWhereNotNull('online_request_id')->exists()
            || DB::table('online_order_requests')->exists()) {
            throw new RuntimeException('Refusing rollback: online purchase data exists; archive/reconcile before removing schema.');
        }
        DB::statement('ALTER TABLE orders
            DROP CHECK orders_purchase_link_check,
            DROP FOREIGN KEY orders_online_request_foreign,
            DROP INDEX orders_online_request_unique,
            DROP INDEX orders_purchase_status_time_index,
            DROP COLUMN online_request_id,
            DROP COLUMN purchase_type');
    }
};
