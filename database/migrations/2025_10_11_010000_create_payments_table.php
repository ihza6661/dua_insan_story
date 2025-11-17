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
        if (!Schema::hasTable('payments')) {
            Schema::create('payments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('order_id')->constrained('orders')->onDelete('cascade');
                $table->string('transaction_id')->unique();
                $table->string('payment_gateway')->default('midtrans');
                $table->decimal('amount', 15, 2);
                $table->string('status')->default('pending');
                $table->enum('payment_type', ['dp', 'final', 'full'])->nullable();
                $table->string('snap_token')->nullable();
                $table->text('raw_response')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
