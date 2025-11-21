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
        Schema::create('invitation_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->string('bride_full_name');
            $table->string('groom_full_name');
            $table->string('bride_nickname');
            $table->string('groom_nickname');
            $table->string('bride_parents');
            $table->string('groom_parents');
            $table->date('akad_date');
            $table->time('akad_time');
            $table->text('akad_location');
            $table->date('reception_date');
            $table->time('reception_time');
            $table->text('reception_location');
            $table->string('gmaps_link')->nullable();
            $table->string('prewedding_photo_path')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invitation_details');
    }
};
