<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $driver = DB::connection()->getDriverName();

        // Update existing records to use Title Case values
        DB::statement("UPDATE orders SET order_status = 'Pending Payment' WHERE order_status = 'pending_payment'");
        DB::statement("UPDATE orders SET order_status = 'Partially Paid' WHERE order_status = 'partially_paid'");
        DB::statement("UPDATE orders SET order_status = 'Processing' WHERE order_status = 'processing'");
        DB::statement("UPDATE orders SET order_status = 'Design Approval' WHERE order_status = 'design_approval'");
        DB::statement("UPDATE orders SET order_status = 'In Production' WHERE order_status = 'in_production'");
        DB::statement("UPDATE orders SET order_status = 'Shipped' WHERE order_status = 'shipped'");
        DB::statement("UPDATE orders SET order_status = 'Delivered' WHERE order_status = 'delivered'");
        DB::statement("UPDATE orders SET order_status = 'Completed' WHERE order_status = 'completed'");
        DB::statement("UPDATE orders SET order_status = 'Cancelled' WHERE order_status = 'cancelled'");
        DB::statement("UPDATE orders SET order_status = 'Failed' WHERE order_status = 'failed'");
        DB::statement("UPDATE orders SET order_status = 'Refunded' WHERE order_status = 'refunded'");
        DB::statement("UPDATE orders SET order_status = 'Paid' WHERE order_status = 'paid'");

        // Only modify enum for MySQL (SQLite doesn't support ENUM and tests use TEXT)
        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE orders MODIFY COLUMN order_status ENUM(
                'Pending Payment',
                'Partially Paid',
                'Paid',
                'Processing',
                'Design Approval',
                'In Production',
                'Shipped',
                'Delivered',
                'Completed',
                'Cancelled',
                'Failed',
                'Refunded'
            ) NOT NULL DEFAULT 'Pending Payment'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = DB::connection()->getDriverName();

        // Update existing records back to snake_case
        DB::statement("UPDATE orders SET order_status = 'pending_payment' WHERE order_status = 'Pending Payment'");
        DB::statement("UPDATE orders SET order_status = 'partially_paid' WHERE order_status = 'Partially Paid'");
        DB::statement("UPDATE orders SET order_status = 'processing' WHERE order_status = 'Processing'");
        DB::statement("UPDATE orders SET order_status = 'design_approval' WHERE order_status = 'Design Approval'");
        DB::statement("UPDATE orders SET order_status = 'in_production' WHERE order_status = 'In Production'");
        DB::statement("UPDATE orders SET order_status = 'shipped' WHERE order_status = 'Shipped'");
        DB::statement("UPDATE orders SET order_status = 'delivered' WHERE order_status = 'Delivered'");
        DB::statement("UPDATE orders SET order_status = 'completed' WHERE order_status = 'Completed'");
        DB::statement("UPDATE orders SET order_status = 'cancelled' WHERE order_status = 'Cancelled'");
        DB::statement("UPDATE orders SET order_status = 'failed' WHERE order_status = 'Failed'");
        DB::statement("UPDATE orders SET order_status = 'refunded' WHERE order_status = 'Refunded'");
        DB::statement("UPDATE orders SET order_status = 'paid' WHERE order_status = 'Paid'");

        // Only modify enum for MySQL
        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE orders MODIFY COLUMN order_status ENUM(
                'pending_payment',
                'pending',
                'processing',
                'design_approval',
                'in_production',
                'shipped',
                'completed',
                'cancelled',
                'failed',
                'refunded'
            ) NOT NULL DEFAULT 'pending_payment'");
        }
    }
};
