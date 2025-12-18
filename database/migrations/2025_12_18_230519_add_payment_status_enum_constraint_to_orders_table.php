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
        // First, clean up any invalid payment statuses
        // Allow empty string for legacy completed/delivered orders
        DB::table('orders')->whereNotIn('payment_status', [
            '',
            'pending',
            'paid',
            'partially_paid',
            'failed',
            'cancelled',
            'refunded',
        ])->update(['payment_status' => '']);

        $driver = DB::connection()->getDriverName();

        // Only apply enum constraint on MySQL
        // PostgreSQL enum handling is complex and not needed with application-level validation
        if ($driver === 'mysql') {
            Schema::table('orders', function (Blueprint $table) {
                $table->enum('payment_status', [
                    'pending',
                    'paid',
                    'partially_paid',
                    'failed',
                    'cancelled',
                    'refunded',
                ])->default('pending')->change();
            });
        }
        // For PostgreSQL and other databases: rely on application validation via model constants
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('payment_status')->nullable()->change();
        });
    }
};
