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
        Schema::create('abandoned_carts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('session_id')->nullable()->index();
            $table->string('email');
            $table->string('name')->nullable();
            $table->json('cart_items'); // Store cart items as JSON
            $table->decimal('cart_total', 10, 2);
            $table->integer('items_count');
            $table->string('recovery_token', 64)->unique();
            $table->timestamp('abandoned_at');
            $table->timestamp('first_reminder_sent_at')->nullable();
            $table->timestamp('second_reminder_sent_at')->nullable();
            $table->timestamp('third_reminder_sent_at')->nullable();
            $table->timestamp('recovered_at')->nullable();
            $table->foreignId('recovered_order_id')->nullable()->constrained('orders')->onDelete('set null');
            $table->boolean('is_recovered')->default(false);
            $table->string('recovery_source')->nullable(); // 'email_1h', 'email_24h', 'email_3d', 'direct'
            $table->timestamps();

            // Indexes for performance
            $table->index('email');
            $table->index('abandoned_at');
            $table->index('is_recovered');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('abandoned_carts');
    }
};
