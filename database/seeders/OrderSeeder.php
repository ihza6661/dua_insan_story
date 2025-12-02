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
        $customer = User::where('email', 'customer@example.com')->first();
        
        if (!$customer) {
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

        // Create 5 completed orders
        $orderStatuses = [
            Order::STATUS_DELIVERED,
            Order::STATUS_DELIVERED,
            Order::STATUS_COMPLETED,
            Order::STATUS_DELIVERED,
            Order::STATUS_COMPLETED,
        ];

        $shippingServices = ['JNE REG', 'J&T Express', 'SiCepat REG', 'AnterAja REG', 'Ninja Express'];
        $couriers = ['JNE', 'J&T', 'SiCepat', 'AnterAja', 'Ninja'];

        foreach ($orderStatuses as $index => $status) {
            // Generate unique order number
            $orderNumber = 'ORD-' . date('Ymd') . '-' . str_pad($index + 1, 4, '0', STR_PAD_LEFT);

            // Calculate dates (orders from 1-3 months ago)
            $daysAgo = rand(30, 90);
            $createdAt = now()->subDays($daysAgo);
            $deliveredAt = $createdAt->copy()->addDays(rand(5, 10));
            $completedAt = $status === Order::STATUS_COMPLETED 
                ? $deliveredAt->copy()->addDays(rand(1, 5)) 
                : null;

            // Select 1-3 random products for this order
            $orderProducts = $products->random(rand(1, 3));
            
            $totalAmount = 0;
            $shippingCost = rand(15000, 35000);

            // Create order
            $order = new Order([
                'customer_id' => $customer->id,
                'order_number' => $orderNumber,
                'total_amount' => 0, // Will update after calculating items
                'shipping_address' => $shippingAddress,
                'shipping_cost' => $shippingCost,
                'shipping_service' => $shippingServices[$index],
                'courier' => $couriers[$index],
                'payment_gateway' => 'midtrans',
                'payment_status' => 'paid',
            ]);

            // Manually set order_status (since it's guarded)
            $order->order_status = $status;
            $order->created_at = $createdAt;
            $order->updated_at = $status === Order::STATUS_COMPLETED ? $completedAt : $deliveredAt;
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

            $this->command->info("Created order: {$orderNumber} - Status: {$status} - Total: Rp " . number_format($order->total_amount));
        }

        $this->command->info('✅ Successfully created 5 completed orders!');
    }
}
