<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('addresses', function (Blueprint $table) {
            // Add fields for multiple addresses support
            $table->string('label', 50)->default('Home')->after('user_id'); // e.g., "Home", "Office", "Store"
            $table->string('recipient_name', 100)->nullable()->after('label');
            $table->string('recipient_phone', 20)->nullable()->after('recipient_name');
            $table->boolean('is_default')->default(false)->after('country');
            
            // Add index for faster queries
            $table->index(['user_id', 'is_default']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('addresses', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'is_default']);
            $table->dropColumn(['label', 'recipient_name', 'recipient_phone', 'is_default']);
        });
    }
};
