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
        Schema::create('digital_invitations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('order_id')->nullable()->constrained('orders')->onDelete('set null');
            $table->foreignId('template_id')->constrained('invitation_templates')->onDelete('restrict');
            
            // URL configuration
            $table->string('slug', 50)->unique(); // Auto-generated: "inv-abc123xyz"
            $table->enum('status', ['draft', 'active', 'expired'])->default('draft');
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('expires_at')->nullable(); // activated_at + 12 months
            
            // MVP: Simple analytics
            $table->unsignedBigInteger('view_count')->default(0);
            $table->timestamp('last_viewed_at')->nullable();
            
            $table->timestamps();
            
            $table->index(['user_id', 'status']);
            $table->index('status');
            $table->index('expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('digital_invitations');
    }
};
