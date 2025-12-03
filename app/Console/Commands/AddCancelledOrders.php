<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Console\Command;

class AddCancelledOrders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:add-cancelled-orders {--count=3 : Number of cancelled orders to create}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add cancelled orders to database (non-destructive, for production)';

    private array $shippingServices = ['JNE REG', 'J&T Express', 'SiCepat REG', 'AnterAja REG', 'Ninja Express', 'POS Indonesia'];
    private array $couriers = ['JNE', 'J&T', 'SiCepat', 'AnterAja', 'Ninja', 'POS'];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $count = (int) $this->option('count');

        $this->info("Adding {$count} cancelled orders to database...");

        // Find customer user
        $customer = User::where('role', 'customer')->first();

        if (! $customer) {
            $this->error('No customer user found! Cannot create orders.');
            return Command::FAILURE;
        }

        $this->info("Found customer: {$customer->email}");

        // Get products
        $products = Product::take(10)->get();

        if ($products->count() < 3) {
            $this->error('Not enough products! Need at least 3 products.');
            return Command::FAILURE;
        }

        $this->info("Found {$products->count()} products");

        // Get customer address or use default
        $address = $customer->address;
        $shippingAddress = $address
            ? $address->full_address
            : 'Jl. Karet Komp. Surya Kencana 1, Kota Pontianak, Kalimantan Barat 71111, Indonesia';

        // Find the latest order number
        $latestOrder = Order::orderBy('id', 'desc')->first();
        $orderCounter = $latestOrder ? $latestOrder->id + 1 : 1;

        $this->info("\nCreating cancelled orders...\n");

        $created = 0;
        for ($i = 0; $i < $count; $i++) {
            $daysAgo = rand(7, 30); // Orders from 1 week to 1 month ago
            $createdAt = now()->subDays($daysAgo);

            $order = $this->createCancelledOrder(
                $customer,
                $products,
                $shippingAddress,
                $orderCounter + $i,
                $createdAt
            );

            if ($order) {
                $created++;
                $this->info("❌ {$order->order_number} - Cancelled - Rp ".number_format($order->total_amount)." ({$createdAt->format('Y-m-d')})");
            }
        }

        $this->info("\n✅ Successfully created {$created} cancelled orders!");
        $this->info("Total orders in database: ".Order::count());
        $this->info("Cancelled orders: ".Order::where('order_status', Order::STATUS_CANCELLED)->count());

        return Command::SUCCESS;
    }

    /**
     * Create a single cancelled order with items
     */
    private function createCancelledOrder(
        User $customer,
        $products,
        string $shippingAddress,
        int $orderNumber,
        $createdAt
    ): ?Order {
        // Generate unique order number
        $orderNumberStr = 'ORD-'.$createdAt->format('Ymd').'-'.str_pad($orderNumber, 4, '0', STR_PAD_LEFT);

        // Check if order number already exists
        if (Order::where('order_number', $orderNumberStr)->exists()) {
            $this->warn("Order {$orderNumberStr} already exists, skipping...");
            return null;
        }

        // Select 1-3 random products for this order
        $orderProducts = $products->random(rand(1, 3));

        $totalAmount = 0;
        $shippingCost = rand(15000, 50000);

        // Random shipping service
        $serviceIndex = array_rand($this->shippingServices);

        // Create order
        $order = new Order([
            'customer_id' => $customer->id,
            'order_number' => $orderNumberStr,
            'total_amount' => 0, // Will update after calculating items
            'shipping_address' => $shippingAddress,
            'shipping_cost' => $shippingCost,
            'shipping_service' => $this->shippingServices[$serviceIndex],
            'courier' => $this->couriers[$serviceIndex],
            'payment_gateway' => 'midtrans',
            'payment_status' => 'pending', // Cancelled orders have pending payment
        ]);

        // Set order_status (guarded field)
        $order->order_status = Order::STATUS_CANCELLED;
        $order->created_at = $createdAt;
        $order->updated_at = $createdAt->copy()->addHours(rand(1, 12)); // Cancelled shortly after creation

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
                'product_variant_id' => null,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'sub_total' => $subTotal,
            ]);
        }

        // Update order total amount (items + shipping)
        $order->total_amount = $totalAmount + $shippingCost;
        $order->save();

        return $order;
    }
}
