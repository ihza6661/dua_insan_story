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
        // Orders table indexes
        Schema::table('orders', function (Blueprint $table) {
            $table->index('order_status', 'idx_orders_order_status');
            $table->index('payment_status', 'idx_orders_payment_status');
            $table->index('created_at', 'idx_orders_created_at');
            $table->index(['customer_id', 'created_at'], 'idx_orders_customer_created');
            $table->index(['order_status', 'created_at'], 'idx_orders_status_created');
            $table->index(['payment_status', 'created_at'], 'idx_orders_payment_created');
        });

        // Products table indexes
        Schema::table('products', function (Blueprint $table) {
            $table->index('is_active', 'idx_products_is_active');
            $table->index(['category_id', 'is_active'], 'idx_products_category_active');
            $table->index('name', 'idx_products_name');
        });

        // Product variants table indexes
        Schema::table('product_variants', function (Blueprint $table) {
            $table->index('stock', 'idx_variants_stock');
            $table->index(['product_id', 'stock'], 'idx_variants_product_stock');
            $table->index('price', 'idx_variants_price');
        });

        // Payments table indexes
        Schema::table('payments', function (Blueprint $table) {
            $table->index('status', 'idx_payments_status');
            $table->index(['order_id', 'status'], 'idx_payments_order_status');
            $table->index('payment_type', 'idx_payments_type');
            $table->index('created_at', 'idx_payments_created_at');
        });

        // Users table indexes
        Schema::table('users', function (Blueprint $table) {
            $table->index('role', 'idx_users_role');
            $table->index(['role', 'created_at'], 'idx_users_role_created');
        });

        // Order cancellation requests table indexes
        Schema::table('order_cancellation_requests', function (Blueprint $table) {
            $table->index('status', 'idx_cancellations_status');
            $table->index(['order_id', 'status'], 'idx_cancellations_order_status');
            $table->index('created_at', 'idx_cancellations_created_at');
        });

        // Order items table indexes (for dashboard queries)
        Schema::table('order_items', function (Blueprint $table) {
            $table->index('order_id', 'idx_order_items_order_id');
            $table->index('product_id', 'idx_order_items_product_id');
        });

        // Cart items table indexes
        Schema::table('cart_items', function (Blueprint $table) {
            $table->index(['cart_id', 'product_id'], 'idx_cart_items_cart_product');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Orders table
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex('idx_orders_order_status');
            $table->dropIndex('idx_orders_payment_status');
            $table->dropIndex('idx_orders_created_at');
            $table->dropIndex('idx_orders_customer_created');
            $table->dropIndex('idx_orders_status_created');
            $table->dropIndex('idx_orders_payment_created');
        });

        // Products table
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex('idx_products_is_active');
            $table->dropIndex('idx_products_category_active');
            $table->dropIndex('idx_products_name');
        });

        // Product variants table
        Schema::table('product_variants', function (Blueprint $table) {
            $table->dropIndex('idx_variants_stock');
            $table->dropIndex('idx_variants_product_stock');
            $table->dropIndex('idx_variants_price');
        });

        // Payments table
        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex('idx_payments_status');
            $table->dropIndex('idx_payments_order_status');
            $table->dropIndex('idx_payments_type');
            $table->dropIndex('idx_payments_created_at');
        });

        // Users table
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('idx_users_role');
            $table->dropIndex('idx_users_role_created');
        });

        // Order cancellation requests table
        Schema::table('order_cancellation_requests', function (Blueprint $table) {
            $table->dropIndex('idx_cancellations_status');
            $table->dropIndex('idx_cancellations_order_status');
            $table->dropIndex('idx_cancellations_created_at');
        });

        // Order items table
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropIndex('idx_order_items_order_id');
            $table->dropIndex('idx_order_items_product_id');
        });

        // Cart items table
        Schema::table('cart_items', function (Blueprint $table) {
            $table->dropIndex('idx_cart_items_cart_product');
        });
    }
};
