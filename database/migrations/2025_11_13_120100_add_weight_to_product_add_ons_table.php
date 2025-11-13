<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_add_ons', function (Blueprint $table) {
            $table->unsignedInteger('weight')->nullable()->after('add_on_id');
        });
    }

    public function down(): void
    {
        Schema::table('product_add_ons', function (Blueprint $table) {
            $table->dropColumn('weight');
        });
    }
};
