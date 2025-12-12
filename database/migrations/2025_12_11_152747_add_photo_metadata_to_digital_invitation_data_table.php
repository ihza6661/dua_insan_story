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
        Schema::table('digital_invitation_data', function (Blueprint $table) {
            $table->json('photo_metadata')->nullable()->after('photo_paths');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('digital_invitation_data', function (Blueprint $table) {
            $table->dropColumn('photo_metadata');
        });
    }
};
