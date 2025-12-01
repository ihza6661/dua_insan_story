<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('design_proofs', function (Blueprint $table) {
            // Add uploaded_by to track which admin uploaded the proof
            $table->foreignId('uploaded_by')->nullable()->after('order_item_id')->constrained('users')->nullOnDelete();

            // Add file metadata
            $table->string('file_name')->nullable()->after('file_url');
            $table->string('file_type')->nullable()->after('file_name');
            $table->unsignedBigInteger('file_size')->nullable()->after('file_type')->comment('File size in bytes');

            // Add thumbnail for quick preview
            $table->string('thumbnail_url')->nullable()->after('file_size');

            // Add reviewed_at timestamp
            $table->timestamp('reviewed_at')->nullable()->after('status');
            $table->foreignId('reviewed_by')->nullable()->after('reviewed_at')->constrained('users')->nullOnDelete();

            // Add notification tracking
            $table->boolean('customer_notified')->default(false)->after('reviewed_by');
            $table->timestamp('customer_notified_at')->nullable()->after('customer_notified');
        });

        // Enhanced status with rejected option - using raw SQL for PostgreSQL compatibility
        if (config('database.default') === 'pgsql') {
            DB::statement('ALTER TABLE design_proofs DROP CONSTRAINT IF EXISTS design_proofs_status_check');
            DB::statement("ALTER TABLE design_proofs ADD CONSTRAINT design_proofs_status_check CHECK (status IN ('pending_approval', 'approved', 'revision_requested', 'rejected'))");
        } else {
            // MySQL/MariaDB
            Schema::table('design_proofs', function (Blueprint $table) {
                $table->enum('status', ['pending_approval', 'approved', 'revision_requested', 'rejected'])->default('pending_approval')->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('design_proofs', function (Blueprint $table) {
            $table->dropForeign(['uploaded_by']);
            $table->dropForeign(['reviewed_by']);
            $table->dropColumn([
                'uploaded_by',
                'file_name',
                'file_type',
                'file_size',
                'thumbnail_url',
                'reviewed_at',
                'reviewed_by',
                'customer_notified',
                'customer_notified_at',
            ]);
        });
    }
};
