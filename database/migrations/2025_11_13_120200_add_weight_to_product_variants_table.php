<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('product_variants', 'weight')) {
            Schema::table('product_variants', function (Blueprint $table) {
                $table->unsignedInteger('weight')->nullable()->after('price');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('product_variants', 'weight')) {
            Schema::table('product_variants', function (Blueprint $table) {
                $table->dropColumn('weight');
            });
        }
    }
};
