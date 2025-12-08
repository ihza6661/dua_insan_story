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
        Schema::create('template_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('template_id')->constrained('invitation_templates')->onDelete('cascade');
            $table->string('field_key', 100); // 'bride_name', 'custom_quote', 'ceremony_time'
            $table->string('field_label', 255); // 'Nama Mempelai Wanita'
            $table->enum('field_type', ['text', 'textarea', 'date', 'time', 'url', 'email', 'phone', 'image', 'color']);
            $table->string('field_category', 50)->default('general'); // 'couple', 'event', 'venue', 'design'
            $table->text('placeholder')->nullable();
            $table->text('default_value')->nullable();
            $table->json('validation_rules')->nullable(); // {"required": true, "max": 255}
            $table->text('help_text')->nullable();
            $table->integer('display_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->unique(['template_id', 'field_key'], 'unique_field_per_template');
            $table->index(['template_id', 'is_active'], 'idx_template_active');
            $table->index(['template_id', 'display_order'], 'idx_display_order');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('template_fields');
    }
};
