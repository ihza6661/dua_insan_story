<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // --- 1. Update orders table ---
        Schema::table('orders', function (Blueprint $table) {
            // Update enum values for improved clarity and DP support
            $table->enum('order_status', [
                'Pending Payment',    // Waiting for initial DP
                'Partially Paid',     // DP received (50%)
                'Paid',               // Full payment received
                'Processing',         // Order being prepared
                'Design Approval',    // Waiting for design confirmation
                'In Production',      // Being printed or produced
                'Shipped',            // Sent to customer
                'Delivered',          // Arrived at destination
                'Completed',          // Customer confirmed received or timeout
                'Cancelled',          // Cancelled before or after payment
                'Failed',             // Payment failure
                'Refunded',           // Money returned
            ])->default('Pending Payment')->change();

            // Add timestamp tracking, checking if they exist first
            if (!Schema::hasColumn('orders', 'shipped_at')) {
                $table->timestamp('shipped_at')->nullable()->after('updated_at');
            }
            if (!Schema::hasColumn('orders', 'delivered_at')) {
                $table->timestamp('delivered_at')->nullable()->after('shipped_at');
            }
            if (!Schema::hasColumn('orders', 'completed_at')) {
                $table->timestamp('completed_at')->nullable()->after('delivered_at');
            }
        });

        // --- 2. Update payments table ---
        Schema::table('payments', function (Blueprint $table) {
            // Check if the unique index exists using a raw query before trying to drop it.
            $indexExists = collect(DB::select("SHOW INDEX FROM payments WHERE Key_name = 'payments_order_id_unique'"))->isNotEmpty();

            if ($indexExists) {
                $table->dropForeign(['order_id']);
                $table->dropUnique('payments_order_id_unique');
                $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');
            }

            // Add payment type column for DP / Final distinction
            if (!Schema::hasColumn('payments', 'payment_type')) {
                $table->enum('payment_type', ['dp', 'final', 'full'])->nullable()->after('amount');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'shipped_at')) {
                $table->dropColumn('shipped_at');
            }
            if (Schema::hasColumn('orders', 'delivered_at')) {
                $table->dropColumn('delivered_at');
            }
            if (Schema::hasColumn('orders', 'completed_at')) {
                $table->dropColumn('completed_at');
            }
        });

        Schema::table('payments', function (Blueprint $table) {
            if (Schema::hasColumn('payments', 'payment_type')) {
                $table->dropColumn('payment_type');
            }

            $databaseName = DB::getDatabaseName();
            $foreignKeys = DB::select("SELECT * FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'payments' AND COLUMN_NAME = 'order_id' AND REFERENCED_TABLE_NAME IS NOT NULL", [$databaseName]);

            if (count($foreignKeys) > 0) {
                $table->dropForeign(['order_id']);
            }

            $indexExists = collect(DB::select("SHOW INDEX FROM payments WHERE Key_name = 'payments_order_id_unique'"))->isEmpty();
            if($indexExists) {
                $table->unique('order_id', 'payments_order_id_unique');
            }
            
            $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');
        });
    }
};
