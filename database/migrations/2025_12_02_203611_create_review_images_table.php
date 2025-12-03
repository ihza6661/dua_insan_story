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
        if (! Schema::hasTable('review_images')) {
            Schema::create('review_images', function (Blueprint $table) {
                $table->id();
                $table->foreignId('review_id')->constrained()->onDelete('cascade');
                $table->string('image_path');
                $table->string('alt_text')->nullable();
                $table->unsignedSmallInteger('display_order')->default(0);
                $table->timestamps();

                // Index for performance
                $table->index('review_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('review_images');
    }
};
