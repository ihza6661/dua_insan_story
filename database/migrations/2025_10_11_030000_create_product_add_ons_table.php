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
        if (!Schema::hasTable('product_add_ons')) {
            Schema::create('product_add_ons', function (Blueprint $table) {
                $table->id();
                $table->foreignId('product_id')->constrained('products');
                $table->foreignId('add_on_id')->constrained('add_ons');
                $table->unsignedInteger('weight')->nullable();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_add_ons');
    }
};
