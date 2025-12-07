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
        Schema::create('invitation_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // "Sakeenah - Islamic Modern"
            $table->string('slug')->unique(); // "sakeenah-islamic-modern"
            $table->text('description')->nullable();
            $table->string('thumbnail_image'); // Preview image for catalog
            $table->decimal('price', 10, 2)->default(150000); // Fixed price for MVP
            $table->string('template_component'); // "SakenahTemplate" or "SimpleWeddingTemplate"
            $table->boolean('is_active')->default(true);
            $table->integer('usage_count')->default(0); // Track popularity
            $table->timestamps();
            
            $table->index(['is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invitation_templates');
    }
};
