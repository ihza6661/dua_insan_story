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
        DB::table('payments')->whereNotIn('status', [
            'pending',
            'paid',
            'failed',
            'cancelled',
            'refunded',
        ])->update(['status' => 'pending']);

        // Change status from string to enum
        Schema::table('payments', function (Blueprint $table) {
            $table->enum('status', [
                'pending',
                'paid',
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
        Schema::table('payments', function (Blueprint $table) {
            $table->string('status')->default('pending')->change();
        });
    }
};
