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
        Schema::table('invitation_details', function (Blueprint $table) {
            // Change gmaps_link from VARCHAR(255) to TEXT to support long Google Maps URLs
            $table->text('gmaps_link')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invitation_details', function (Blueprint $table) {
            // Revert back to string (VARCHAR 255)
            $table->string('gmaps_link')->nullable()->change();
        });
    }
};
