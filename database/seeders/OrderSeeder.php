<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;

class OrderSeeder extends Seeder
{
    public function run(): void
    {
        // Clear existing orders first (safely with foreign key constraints)
        $this->command->info('🗑️  Clearing existing orders...');

        // Delete related records first
        \Illuminate\Support\Facades\DB::table('design_proofs')->delete();
        \Illuminate\Support\Facades\DB::table('order_cancellation_requests')->delete();
        \Illuminate\Support\Facades\DB::table('payments')->delete();
        \Illuminate\Support\Facades\DB::table('order_items')->delete();
        \Illuminate\Support\Facades\DB::table('orders')->delete();

        $customer = User::where('email', 'customer@example.com')->first();

        if (! $customer) {
            $this->command->error('Customer user not found! Please run UserSeeder first.');

            return;
        }

        $products = Product::take(10)->get();

        if ($products->count() < 5) {
            $this->command->error('Not enough products! Please run ProductsTableSeeder first.');

            return;
        }

        // Get customer address for shipping
        $address = $customer->address;
        $shippingAddress = $address
            ? $address->full_address
            : 'Jl. Karet Komp. Surya Kencana 1, Kota Pontianak, Kalimantan Barat 71111, Indonesia';

        $shippingServices = ['JNE REG', 'J&T Express', 'SiCepat REG', 'AnterAja REG', 'Ninja Express', 'POS Indonesia'];
        $couriers = ['JNE', 'J&T', 'SiCepat', 'AnterAja', 'Ninja', 'POS'];

        $orderCounter = 1;

        // ====== CREATE ORDERS FOR THE PAST 7 DAYS (for weekly revenue chart) ======
        $this->command->info('📊 Creating orders for weekly revenue chart...');

        for ($daysAgo = 6; $daysAgo >= 0; $daysAgo--) {
            // Create 2-4 completed orders per day
            $ordersPerDay = rand(2, 4);

            for ($i = 0; $i < $ordersPerDay; $i++) {
                $createdAt = now()->subDays($daysAgo)->setHour(rand(8, 20))->setMinute(rand(0, 59));

                $this->createOrder(
                    $customer,
                    $products,
                    $shippingAddress,
                    $shippingServices,
                    $couriers,
                    $orderCounter++,
                    Order::STATUS_COMPLETED,
                    $createdAt
                );
            }
        }

        // ====== CREATE ADDITIONAL ORDERS WITH VARIOUS STATUSES ======
        $this->command->info('📦 Creating orders with various statuses...');

        // Orders from 1-3 months ago (completed/delivered)
        $historicalStatuses = [
            Order::STATUS_COMPLETED,
            Order::STATUS_COMPLETED,
            Order::STATUS_DELIVERED,
            Order::STATUS_COMPLETED,
            Order::STATUS_DELIVERED,
        ];

        foreach ($historicalStatuses as $status) {
            $daysAgo = rand(30, 90);
            $createdAt = now()->subDays($daysAgo);

            $this->createOrder(
                $customer,
                $products,
                $shippingAddress,
                $shippingServices,
                $couriers,
                $orderCounter++,
                $status,
                $createdAt
            );
        }

        // Current/Recent orders with different statuses (for testing different order states)
        $recentStatuses = [
            Order::STATUS_PENDING_PAYMENT,
            Order::STATUS_PAID,
            Order::STATUS_PROCESSING,
            Order::STATUS_DESIGN_APPROVAL,
            Order::STATUS_IN_PRODUCTION,
            Order::STATUS_SHIPPED,
        ];

        foreach ($recentStatuses as $status) {
            $daysAgo = rand(0, 7);
            $createdAt = now()->subDays($daysAgo);

            $this->createOrder(
                $customer,
                $products,
                $shippingAddress,
                $shippingServices,
                $couriers,
                $orderCounter++,
                $status,
                $createdAt
            );
        }

        $this->command->info('✅ Successfully created '.($orderCounter - 1).' orders!');
        $this->command->info('📈 Weekly revenue chart should now display data for the past 7 days.');
        $this->command->info('💰 Total revenue includes only "Completed" orders.');
    }

    /**
     * Create a single order with items
     */
    private function createOrder(
        User $customer,
        $products,
        string $shippingAddress,
        array $shippingServices,
        array $couriers,
        int $orderNumber,
        string $status,
        $createdAt
    ): void {
        // Generate unique order number
        $orderNumberStr = 'ORD-'.$createdAt->format('Ymd').'-'.str_pad($orderNumber, 4, '0', STR_PAD_LEFT);

        // Select 1-3 random products for this order
        $orderProducts = $products->random(rand(1, 3));

        $totalAmount = 0;
        $shippingCost = rand(15000, 50000);

        // Random shipping service
        $serviceIndex = array_rand($shippingServices);

        // Create order
        $order = new Order([
            'customer_id' => $customer->id,
            'order_number' => $orderNumberStr,
            'total_amount' => 0, // Will update after calculating items
            'shipping_address' => $shippingAddress,
            'shipping_cost' => $shippingCost,
            'shipping_service' => $shippingServices[$serviceIndex],
            'courier' => $couriers[$serviceIndex],
            'payment_gateway' => 'midtrans',
            'payment_status' => in_array($status, [Order::STATUS_PENDING_PAYMENT]) ? 'pending' : 'paid',
        ]);

        // Manually set order_status (since it's guarded)
        $order->order_status = $status;
        $order->created_at = $createdAt;

        // Set updated_at based on status
        if ($status === Order::STATUS_COMPLETED) {
            $order->updated_at = $createdAt->copy()->addDays(rand(7, 14));
        } elseif (in_array($status, [Order::STATUS_DELIVERED, Order::STATUS_SHIPPED])) {
            $order->updated_at = $createdAt->copy()->addDays(rand(5, 10));
        } else {
            $order->updated_at = $createdAt->copy()->addHours(rand(1, 48));
        }

        $order->save();

        // Create order items
        foreach ($orderProducts as $product) {
            $quantity = rand(1, 3);
            $unitPrice = $product->base_price;
            $subTotal = $unitPrice * $quantity;
            $totalAmount += $subTotal;

            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $product->id,
                'product_variant_id' => null, // Simplified - no variants
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'sub_total' => $subTotal,
            ]);
        }

        // Update order total amount (items + shipping)
        $order->total_amount = $totalAmount + $shippingCost;
        $order->save();

        $statusEmoji = $status === Order::STATUS_COMPLETED ? '✅' : '📦';
        $this->command->info("{$statusEmoji} {$orderNumberStr} - {$status} - Rp ".number_format($order->total_amount)." ({$createdAt->format('Y-m-d')})");
    }
}
