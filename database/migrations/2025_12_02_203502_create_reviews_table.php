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
        if (! Schema::hasTable('reviews')) {
            Schema::create('reviews', function (Blueprint $table) {
                $table->id();
                $table->foreignId('order_item_id')->constrained()->onDelete('cascade');
                $table->foreignId('customer_id')->constrained('users')->onDelete('cascade');
                $table->foreignId('product_id')->constrained()->onDelete('cascade');
                $table->unsignedTinyInteger('rating'); // 1-5 stars
                $table->text('comment')->nullable();
                $table->boolean('is_verified')->default(true); // verified purchase (always true for order-based reviews)
                $table->boolean('is_approved')->default(true); // admin moderation
                $table->boolean('is_featured')->default(false); // featured reviews

                // Admin response
                $table->text('admin_response')->nullable();
                $table->timestamp('admin_responded_at')->nullable();
                $table->foreignId('admin_responder_id')->nullable()->constrained('users')->nullOnDelete();

                // Helpfulness tracking
                $table->unsignedInteger('helpful_count')->default(0);

                $table->timestamps();

                // Prevent duplicate reviews per order item
                $table->unique(['order_item_id', 'customer_id']);

                // Indexes for performance
                $table->index('product_id');
                $table->index('customer_id');
                $table->index(['product_id', 'is_approved']);
                $table->index(['product_id', 'rating']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
