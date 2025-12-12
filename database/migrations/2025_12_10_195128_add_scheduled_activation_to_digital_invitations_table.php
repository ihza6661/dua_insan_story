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
        Schema::table('digital_invitations', function (Blueprint $table) {
            $table->timestamp('scheduled_activation_at')->nullable()->after('activated_at');
            $table->index('scheduled_activation_at'); // For scheduled job queries
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('digital_invitations', function (Blueprint $table) {
            $table->dropIndex(['scheduled_activation_at']);
            $table->dropColumn('scheduled_activation_at');
        });
    }
};
