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
        if (!Schema::hasTable('orders')) {
            Schema::create('orders', function (Blueprint $table) {
                $table->id();
                $table->foreignId('customer_id')->constrained('users');
                $table->string('order_number')->unique();
                $table->decimal('total_amount', 15, 2);
                $table->text('shipping_address');
                $table->enum('order_status', [
                    'Pending Payment',
                    'Partially Paid',
                    'Paid',
                    'Processing',
                    'Design Approval',
                    'In Production',
                    'Shipped',
                    'Delivered',
                    'Completed',
                    'Cancelled',
                    'Failed',
                    'Refunded',
                ])->default('Pending Payment');
                $table->string('payment_status')->nullable();
                $table->decimal('shipping_cost', 15, 2)->default(0);
                $table->string('shipping_service')->nullable();
                $table->string('courier')->nullable();
                $table->string('payment_gateway')->default('midtrans');
                $table->string('snap_token')->nullable();
                $table->timestamp('shipped_at')->nullable();
                $table->timestamp('delivered_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
