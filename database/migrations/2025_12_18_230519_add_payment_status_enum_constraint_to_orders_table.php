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
        DB::table('orders')->whereNotIn('payment_status', [
            'pending',
            'paid',
            'partially_paid',
            'failed',
            'cancelled',
            'refunded',
        ])->update(['payment_status' => 'pending']);

        // Change payment_status from string to enum
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
