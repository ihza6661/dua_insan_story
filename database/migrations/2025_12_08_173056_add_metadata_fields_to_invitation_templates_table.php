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
        Schema::table('invitation_templates', function (Blueprint $table) {
            $table->boolean('has_custom_fields')->default(false)->after('usage_count');
            $table->json('color_themes')->nullable()->after('has_custom_fields')->comment('Available color schemes: {"rose_gold": "#d4a5a5"}');
            $table->json('preview_data')->nullable()->after('color_themes')->comment('Sample data for catalog previews');
            $table->json('export_settings')->nullable()->after('preview_data')->comment('PDF/image configuration');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invitation_templates', function (Blueprint $table) {
            $table->dropColumn(['has_custom_fields', 'color_themes', 'preview_data', 'export_settings']);
        });
    }
};
