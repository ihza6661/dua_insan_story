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
            $table->json('customization_json')->nullable()->after('photo_paths')->comment('Dynamic fields per template');
            $table->string('color_scheme', 50)->nullable()->after('customization_json')->comment('Selected color theme');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('digital_invitation_data', function (Blueprint $table) {
            $table->dropColumn(['customization_json', 'color_scheme']);
        });
    }
};
