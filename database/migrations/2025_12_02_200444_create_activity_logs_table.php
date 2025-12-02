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
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->string('log_type'); // 'order_cancellation', 'order_status_change', etc.
            $table->string('action'); // 'approved', 'rejected', 'created', 'updated'
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null'); // Who performed the action
            $table->string('user_name')->nullable(); // Store name for audit trail even if user deleted
            $table->string('user_role')->nullable(); // admin, customer, etc.
            $table->morphs('subject'); // Polymorphic relation to any model (order, cancellation_request, etc.)
            $table->text('description')->nullable(); // Human-readable description
            $table->json('properties')->nullable(); // Store old/new values, metadata
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamps();

            // Indexes for performance
            $table->index(['log_type', 'created_at']);
            $table->index(['user_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
