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
        Schema::create('digital_invitation_data', function (Blueprint $table) {
            $table->id();
            $table->foreignId('digital_invitation_id')->constrained('digital_invitations')->onDelete('cascade');
            
            // Couple names
            $table->string('bride_name')->nullable();
            $table->string('groom_name')->nullable();
            
            // Event details
            $table->date('event_date')->nullable();
            $table->time('event_time')->nullable();
            $table->string('venue_name')->nullable();
            $table->text('venue_address')->nullable();
            $table->string('venue_maps_url')->nullable();
            
            // Personalization
            $table->text('opening_message')->nullable(); // "Join us to celebrate..."
            
            // Photos (MVP: Store paths as JSON array)
            $table->json('photo_paths')->nullable(); // ["invitations/1/hero.jpg", "invitations/1/gallery1.jpg", ...]
            
            $table->timestamps();
            
            $table->index('digital_invitation_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('digital_invitation_data');
    }
};
