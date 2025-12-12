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
        Schema::create('user_generated_content', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('digital_invitation_id')->nullable()->constrained()->nullOnDelete();
            $table->string('image_path');
            $table->text('caption')->nullable();
            $table->string('instagram_url')->nullable();
            $table->string('instagram_handle')->nullable();
            $table->boolean('is_approved')->default(false);
            $table->boolean('is_featured')->default(false);
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            // Indexes for better query performance
            $table->index(['is_approved', 'is_featured']);
            $table->index('user_id');
            $table->index('product_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_generated_content');
    }
};
