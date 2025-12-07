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
        Schema::table('products', function (Blueprint $table) {
            $table->enum('product_type', ['physical', 'digital'])
                ->default('physical')
                ->after('category_id');
            $table->foreignId('template_id')
                ->nullable()
                ->constrained('invitation_templates')
                ->onDelete('restrict')
                ->after('product_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['template_id']);
            $table->dropColumn(['product_type', 'template_id']);
        });
    }
};
