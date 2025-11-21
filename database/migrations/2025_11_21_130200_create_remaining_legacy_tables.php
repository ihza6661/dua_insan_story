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
        if (!Schema::hasTable('gallery_items')) {
            Schema::create('gallery_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
                $table->string('title')->nullable();
                $table->text('description')->nullable();
                $table->string('category', 100)->nullable();
                $table->string('file_path');
                $table->enum('media_type', ['image', 'video'])->default('image');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('design_proofs')) {
            Schema::create('design_proofs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('order_item_id')->constrained()->cascadeOnDelete();
                $table->unsignedInteger('version')->default(1);
                $table->string('file_url');
                $table->enum('status', ['pending_approval', 'approved', 'revision_requested'])->default('pending_approval');
                $table->text('customer_feedback')->nullable();
                $table->text('admin_notes')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('order_custom_data')) {
            Schema::create('order_custom_data', function (Blueprint $table) {
                $table->id();
                $table->foreignId('order_item_id')->constrained()->cascadeOnDelete();
                $table->json('form_data');
                $table->unique('order_item_id');
            });
        }

        if (!Schema::hasTable('cache')) {
            Schema::create('cache', function (Blueprint $table) {
                $table->string('key')->primary();
                $table->mediumText('value');
                $table->integer('expiration');
            });
        }

        if (!Schema::hasTable('cache_locks')) {
            Schema::create('cache_locks', function (Blueprint $table) {
                $table->string('key')->primary();
                $table->string('owner');
                $table->integer('expiration');
            });
        }

        if (!Schema::hasTable('jobs')) {
            Schema::create('jobs', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('queue');
                $table->longText('payload');
                $table->unsignedTinyInteger('attempts');
                $table->unsignedInteger('reserved_at')->nullable();
                $table->unsignedInteger('available_at');
                $table->unsignedInteger('created_at');
                $table->index('queue');
            });
        }

        if (!Schema::hasTable('job_batches')) {
            Schema::create('job_batches', function (Blueprint $table) {
                $table->string('id')->primary();
                $table->string('name');
                $table->integer('total_jobs');
                $table->integer('pending_jobs');
                $table->integer('failed_jobs');
                $table->longText('failed_job_ids');
                $table->mediumText('options')->nullable();
                $table->integer('cancelled_at')->nullable();
                $table->integer('created_at');
                $table->integer('finished_at')->nullable();
            });
        }

        if (!Schema::hasTable('failed_jobs')) {
            Schema::create('failed_jobs', function (Blueprint $table) {
                $table->id();
                $table->string('uuid')->unique();
                $table->text('connection');
                $table->text('queue');
                $table->longText('payload');
                $table->longText('exception');
                $table->timestamp('failed_at')->useCurrent();
            });
        }

        if (!Schema::hasTable('password_reset_tokens')) {
            Schema::create('password_reset_tokens', function (Blueprint $table) {
                $table->string('email')->primary();
                $table->string('token');
                $table->timestamp('created_at')->nullable();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('design_proofs');
        Schema::dropIfExists('order_custom_data');
        Schema::dropIfExists('gallery_items');
        Schema::dropIfExists('failed_jobs');
        Schema::dropIfExists('job_batches');
        Schema::dropIfExists('jobs');
        Schema::dropIfExists('cache_locks');
        Schema::dropIfExists('cache');
        Schema::dropIfExists('password_reset_tokens');
    }
};
