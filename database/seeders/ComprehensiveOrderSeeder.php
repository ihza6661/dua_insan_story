<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ComprehensiveOrderSeeder extends Seeder
{
    /**
     * Run the database seeder.
     * 
     * This seeder combines:
     * 1. Realistic scenario-based orders (for testing specific flows)
     * 2. Revenue-generating orders (for dashboard analytics)
     * 3. Both physical and digital products
     */
    public function run(): void
    {
        $this->command->info('🗑️  Clearing existing orders...');
        $this->clearExistingOrders();

        $customers = $this->getCustomers();
        if ($customers->count() < 3) {
            $this->command->error('❌ Not enough customers. Please run UserSeeder first.');
            return;
        }

        $physicalProducts = Product::where('product_type', 'physical')->get();
        $digitalProducts = Product::where('product_type', 'digital')->get();

        if ($physicalProducts->isEmpty() && $digitalProducts->isEmpty()) {
            $this->command->error('❌ No products found. Please run ProductsTableSeeder first.');
            return;
        }

        $this->command->info('');
        $this->command->info('══════════════════════════════════════════════════════');
        $this->command->info('  COMPREHENSIVE ORDER SEEDING');
        $this->command->info('══════════════════════════════════════════════════════');
        $this->command->info('');

        // Part 1: Realistic scenario-based orders (for testing)
        $this->createRealisticScenarios($customers, $physicalProducts);

        // Part 2: Historical revenue orders (for dashboard)
        $this->createRevenueOrders($customers, $physicalProducts, $digitalProducts);

        $this->displaySummary();
    }

    /**
     * Clear all existing order-related data
     */
    private function clearExistingOrders(): void
    {
        DB::table('design_proofs')->delete();
        DB::table('order_cancellation_requests')->delete();
        DB::table('invitation_details')->delete();
        DB::table('payments')->delete();
        DB::table('order_items')->delete();
        DB::table('orders')->delete();
    }

    /**
     * Get customer users
     */
    private function getCustomers()
    {
        return User::where('role', 'customer')->get();
    }

    /**
     * Create realistic scenario-based orders for testing
     */
    private function createRealisticScenarios($customers, $physicalProducts): void
    {
        $this->command->info('📦 PART 1: Creating Realistic Test Scenarios');
        $this->command->info('────────────────────────────────────────────');

        if ($physicalProducts->isNotEmpty()) {
            $this->command->info('');
            $this->command->info('🎁 Physical Product Orders:');
            
            $scenarios = [
                ['scenario' => 'completed', 'couple' => 'Budi & Ani', 'qty' => 200, 'days_ago' => 15],
                ['scenario' => 'shipped', 'couple' => 'Ahmad & Siti', 'qty' => 150, 'days_ago' => 7],
                ['scenario' => 'processing', 'couple' => 'Reza & Dita', 'qty' => 300, 'days_ago' => 3],
                ['scenario' => 'large_order', 'couple' => 'Fajar & Rina', 'qty' => 500, 'days_ago' => 5],
                ['scenario' => 'pending', 'couple' => 'Indra & Maya', 'qty' => 100, 'days_ago' => 1],
                ['scenario' => 'cancelled', 'couple' => 'Dewi & Agus', 'qty' => 250, 'days_ago' => 20],
            ];

            foreach ($scenarios as $scenario) {
                $this->createScenarioOrder(
                    $customers->random(),
                    $physicalProducts->random(),
                    $scenario
                );
            }

            // Create one order with multiple items
            if ($physicalProducts->count() >= 3) {
                $this->createMultiItemOrder(
                    $customers->random(),
                    $physicalProducts->random(3)
                );
            }
        }

        $this->command->info('');
    }

    /**
     * Create revenue-generating orders for dashboard analytics
     */
    private function createRevenueOrders($customers, $physicalProducts, $digitalProducts): void
    {
        $this->command->info('📊 PART 2: Creating Revenue Orders (Dashboard Data)');
        $this->command->info('────────────────────────────────────────────');
        $this->command->info('');

        $allProducts = $physicalProducts->merge($digitalProducts);
        
        if ($allProducts->isEmpty()) {
            $this->command->warn('⚠️  No products available for revenue orders.');
            return;
        }

        $orderCounter = 1;

        // Weekly orders (past 7 days) - for weekly revenue chart
        $this->command->info('📅 Weekly Orders (Past 7 Days):');
        for ($daysAgo = 6; $daysAgo >= 0; $daysAgo--) {
            $ordersPerDay = rand(2, 4);

            for ($i = 0; $i < $ordersPerDay; $i++) {
                $createdAt = now()->subDays($daysAgo)->setHour(rand(8, 20))->setMinute(rand(0, 59));
                
                $this->createRevenueOrder(
                    $customers->random(),
                    $allProducts,
                    Order::STATUS_COMPLETED,
                    $createdAt,
                    $orderCounter++
                );
            }
        }

        $this->command->info('');
        $this->command->info('📜 Historical Orders (1-3 Months Ago):');
        
        // Historical orders (1-3 months ago)
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
            
            $this->createRevenueOrder(
                $customers->random(),
                $allProducts,
                $status,
                $createdAt,
                $orderCounter++
            );
        }

        $this->command->info('');
        $this->command->info('🔄 Recent Mixed Status Orders:');
        
        // Recent orders with various statuses
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
            
            $this->createRevenueOrder(
                $customers->random(),
                $allProducts,
                $status,
                $createdAt,
                $orderCounter++
            );
        }

        $this->command->info('');
        $this->command->info('❌ Cancelled Orders:');
        
        // Cancelled orders
        for ($i = 0; $i < 3; $i++) {
            $daysAgo = rand(7, 30);
            $createdAt = now()->subDays($daysAgo);
            
            $this->createRevenueOrder(
                $customers->random(),
                $allProducts,
                Order::STATUS_CANCELLED,
                $createdAt,
                $orderCounter++
            );
        }

        $this->command->info('');
    }

    /**
     * Create a scenario-based order (from PhysicalProductOrdersSeeder)
     */
    private function createScenarioOrder(User $customer, Product $product, array $data): void
    {
        $variant = $product->variants()->inRandomOrder()->first();
        if (!$variant) {
            $this->command->warn("  ⚠️  Product {$product->name} has no variants. Skipping.");
            return;
        }

        $quantity = $data['qty'];
        $subtotal = $variant->price * $quantity;
        $shippingCost = $this->getShippingCost($data['scenario']);
        $totalAmount = $subtotal + $shippingCost;

        $config = $this->getStatusConfig($data['scenario'], $data['days_ago']);

        // Insert order
        $orderId = DB::table('orders')->insertGetId([
            'order_number' => 'ORD-' . strtoupper(Str::random(10)),
            'customer_id' => $customer->id,
            'order_status' => $config['order_status'],
            'payment_status' => $config['payment_status'],
            'subtotal_amount' => $subtotal,
            'shipping_cost' => $shippingCost,
            'shipping_method' => $config['shipping_method'],
            'total_amount' => $totalAmount,
            'shipping_address' => $this->getShippingAddress($data['scenario']),
            'created_at' => $config['created_at'],
            'updated_at' => $config['updated_at'],
        ]);

        // Insert order item
        DB::table('order_items')->insert([
            'order_id' => $orderId,
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'quantity' => $quantity,
            'unit_price' => $variant->price,
            'sub_total' => $subtotal,
            'created_at' => $config['created_at'],
            'updated_at' => $config['updated_at'],
        ]);

        // Insert payment (except for pending)
        if ($data['scenario'] !== 'pending') {
            DB::table('payments')->insert([
                'order_id' => $orderId,
                'payment_type' => 'full',
                'payment_gateway' => 'midtrans',
                'amount' => $totalAmount,
                'status' => $config['payment_db_status'],
                'transaction_id' => 'TRX-' . strtoupper(Str::random(16)),
                'created_at' => $config['created_at'],
                'updated_at' => $config['updated_at'],
            ]);
        }

        $order = Order::find($orderId);
        $statusEmoji = $this->getStatusEmoji($data['scenario']);
        $this->command->info("  {$statusEmoji} {$order->order_number} - {$data['couple']} ({$quantity} pcs) - Rp " . number_format($totalAmount, 0, ',', '.'));
    }

    /**
     * Create an order with multiple items
     */
    private function createMultiItemOrder(User $customer, $products): void
    {
        $subtotal = 0;
        $shippingCost = 30000;
        
        $orderId = DB::table('orders')->insertGetId([
            'order_number' => 'ORD-' . strtoupper(Str::random(10)),
            'customer_id' => $customer->id,
            'order_status' => Order::STATUS_PROCESSING,
            'payment_status' => 'settlement',
            'subtotal_amount' => 0,
            'shipping_cost' => $shippingCost,
            'shipping_method' => 'JNE REG',
            'total_amount' => 0,
            'shipping_address' => 'Jl. Diponegoro No. 55, Yogyakarta 55122',
            'created_at' => now()->subDays(4),
            'updated_at' => now()->subDays(2),
        ]);

        $quantities = [200, 150, 100];
        $itemCount = 0;
        
        foreach ($products as $index => $product) {
            $variant = $product->variants()->inRandomOrder()->first();
            if (!$variant) continue;

            $quantity = $quantities[$index] ?? 100;
            $itemSubtotal = $variant->price * $quantity;
            $subtotal += $itemSubtotal;

            DB::table('order_items')->insert([
                'order_id' => $orderId,
                'product_id' => $product->id,
                'product_variant_id' => $variant->id,
                'quantity' => $quantity,
                'unit_price' => $variant->price,
                'sub_total' => $itemSubtotal,
                'created_at' => now()->subDays(4),
                'updated_at' => now()->subDays(2),
            ]);
            
            $itemCount++;
        }

        DB::table('orders')->where('id', $orderId)->update([
            'subtotal_amount' => $subtotal,
            'total_amount' => $subtotal + $shippingCost,
        ]);

        DB::table('payments')->insert([
            'order_id' => $orderId,
            'payment_type' => 'full',
            'payment_gateway' => 'midtrans',
            'amount' => $subtotal + $shippingCost,
            'status' => 'settlement',
            'transaction_id' => 'TRX-' . strtoupper(Str::random(16)),
            'created_at' => now()->subDays(3),
            'updated_at' => now()->subDays(3),
        ]);

        $order = Order::find($orderId);
        $this->command->info("  📦 {$order->order_number} - Multiple Items ({$itemCount}) - Andi & Lina - Rp " . number_format($order->total_amount, 0, ',', '.'));
    }

    /**
     * Create a revenue-generating order (from OrderSeeder)
     */
    private function createRevenueOrder(User $customer, $products, string $status, $createdAt, int $orderNumber): void
    {
        $orderNumberStr = 'ORD-' . $createdAt->format('Ymd') . '-' . str_pad($orderNumber, 4, '0', STR_PAD_LEFT);

        // Select 1-3 random products
        $orderProducts = $products->random(min(rand(1, 3), $products->count()));
        
        $totalAmount = 0;
        $shippingCost = rand(15000, 50000);
        $shippingServices = ['JNE REG', 'J&T Express', 'SiCepat REG', 'AnterAja REG', 'Ninja Express'];
        $shippingService = $shippingServices[array_rand($shippingServices)];

        // Determine payment status
        $paymentStatus = in_array($status, [Order::STATUS_PENDING_PAYMENT, Order::STATUS_CANCELLED]) 
            ? 'pending' 
            : 'settlement';

        // Create order
        $order = new Order([
            'customer_id' => $customer->id,
            'order_number' => $orderNumberStr,
            'total_amount' => 0,
            'shipping_address' => $this->getRandomShippingAddress(),
            'shipping_cost' => $shippingCost,
            'shipping_method' => $shippingService,
            'payment_gateway' => 'midtrans',
            'payment_status' => $paymentStatus,
        ]);

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
            $quantity = rand(100, 300);
            
            // Get variant or use base price
            $variant = $product->variants()->inRandomOrder()->first();
            $unitPrice = $variant ? $variant->price : $product->base_price;
            
            $subTotal = $unitPrice * $quantity;
            $totalAmount += $subTotal;

            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $product->id,
                'product_variant_id' => $variant?->id,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'sub_total' => $subTotal,
            ]);
        }

        // Update total
        $order->total_amount = $totalAmount + $shippingCost;
        $order->save();

        // Create payment record if paid
        if ($paymentStatus !== 'pending') {
            DB::table('payments')->insert([
                'order_id' => $order->id,
                'payment_type' => 'full',
                'payment_gateway' => 'midtrans',
                'amount' => $order->total_amount,
                'status' => $paymentStatus,
                'transaction_id' => 'TRX-' . strtoupper(Str::random(16)),
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);
        }

        $statusEmoji = $this->getStatusEmojiForStatus($status);
        $this->command->info("  {$statusEmoji} {$orderNumberStr} - {$status} - Rp " . number_format($order->total_amount, 0, ',', '.'));
    }

    /**
     * Display summary statistics
     */
    private function displaySummary(): void
    {
        $totalOrders = Order::count();
        $totalRevenue = Order::where('order_status', Order::STATUS_COMPLETED)->sum('total_amount');
        $pendingOrders = Order::where('order_status', Order::STATUS_PENDING_PAYMENT)->count();
        $completedOrders = Order::where('order_status', Order::STATUS_COMPLETED)->count();

        $this->command->info('');
        $this->command->info('══════════════════════════════════════════════════════');
        $this->command->info('  SEEDING COMPLETE');
        $this->command->info('══════════════════════════════════════════════════════');
        $this->command->info('');
        $this->command->info("📊 Total Orders Created: {$totalOrders}");
        $this->command->info("✅ Completed Orders: {$completedOrders}");
        $this->command->info("⏳ Pending Orders: {$pendingOrders}");
        $this->command->info("💰 Total Revenue (Completed): Rp " . number_format($totalRevenue, 0, ',', '.'));
        $this->command->info('');
        $this->command->info('📈 Weekly revenue chart ready with past 7 days data');
        $this->command->info('🧪 Test scenarios ready for order flow testing');
        $this->command->info('');
    }

    /**
     * Get status configuration for scenario orders
     */
    private function getStatusConfig(string $scenario, int $daysAgo): array
    {
        $created = now()->subDays($daysAgo);
        $updated = now()->subDays(max(0, $daysAgo - 3));
        
        return match ($scenario) {
            'completed' => [
                'order_status' => Order::STATUS_COMPLETED,
                'payment_status' => 'settlement',
                'payment_db_status' => 'settlement',
                'shipping_method' => 'JNE REG',
                'created_at' => $created,
                'updated_at' => $updated,
            ],
            'shipped' => [
                'order_status' => Order::STATUS_SHIPPED,
                'payment_status' => 'settlement',
                'payment_db_status' => 'settlement',
                'shipping_method' => 'JNE YES',
                'created_at' => $created,
                'updated_at' => $updated,
            ],
            'processing' => [
                'order_status' => Order::STATUS_PROCESSING,
                'payment_status' => 'settlement',
                'payment_db_status' => 'settlement',
                'shipping_method' => 'JNE REG',
                'created_at' => $created,
                'updated_at' => $updated,
            ],
            'large_order' => [
                'order_status' => Order::STATUS_IN_PRODUCTION,
                'payment_status' => 'settlement',
                'payment_db_status' => 'settlement',
                'shipping_method' => 'Cargo',
                'created_at' => $created,
                'updated_at' => $updated,
            ],
            'pending' => [
                'order_status' => Order::STATUS_PENDING_PAYMENT,
                'payment_status' => 'pending',
                'payment_db_status' => 'pending',
                'shipping_method' => 'JNE REG',
                'created_at' => $created,
                'updated_at' => $updated,
            ],
            'cancelled' => [
                'order_status' => Order::STATUS_CANCELLED,
                'payment_status' => 'cancel',
                'payment_db_status' => 'cancel',
                'shipping_method' => 'JNE REG',
                'created_at' => $created,
                'updated_at' => $updated,
            ],
            default => [
                'order_status' => Order::STATUS_PENDING_PAYMENT,
                'payment_status' => 'pending',
                'payment_db_status' => 'pending',
                'shipping_method' => 'JNE REG',
                'created_at' => $created,
                'updated_at' => $updated,
            ],
        };
    }

    private function getShippingCost(string $scenario): int
    {
        return match ($scenario) {
            'completed' => 25000,
            'shipped' => 35000,
            'processing' => 20000,
            'large_order' => 75000,
            'pending' => 15000,
            'cancelled' => 25000,
            default => 20000,
        };
    }

    private function getShippingAddress(string $scenario): string
    {
        return match ($scenario) {
            'completed' => 'Jl. Merdeka No. 45, Menteng, Jakarta Pusat 10310',
            'shipped' => 'Jl. Sudirman No. 123, Setiabudi, Jakarta Selatan 12920',
            'processing' => 'Jl. Gatot Subroto No. 88, Jakarta Selatan 12930',
            'large_order' => 'Gedung Merdeka, Jl. Asia Afrika No. 8, Bandung 40111',
            'pending' => 'Jl. Raya Bogor KM 25, Cibubur, Jakarta Timur 13720',
            'cancelled' => 'Jl. Pemuda No. 99, Semarang 50132',
            default => 'Jl. Test No. 1, Jakarta 10000',
        };
    }

    private function getRandomShippingAddress(): string
    {
        $addresses = [
            'Jl. Gatot Subroto No. 45, Jakarta Selatan 12930',
            'Jl. Sudirman No. 88, Jakarta Pusat 10220',
            'Jl. MH Thamrin No. 123, Jakarta Pusat 10310',
            'Jl. Ahmad Yani No. 67, Surabaya 60234',
            'Jl. Diponegoro No. 99, Bandung 40115',
            'Jl. Malioboro No. 12, Yogyakarta 55271',
        ];

        return $addresses[array_rand($addresses)];
    }

    private function getStatusEmoji(string $scenario): string
    {
        return match ($scenario) {
            'completed' => '✅',
            'shipped' => '🚚',
            'processing' => '⚙️',
            'large_order' => '🏭',
            'pending' => '⏳',
            'cancelled' => '❌',
            default => '📦',
        };
    }

    private function getStatusEmojiForStatus(string $status): string
    {
        return match ($status) {
            Order::STATUS_COMPLETED => '✅',
            Order::STATUS_DELIVERED => '🚚',
            Order::STATUS_SHIPPED => '📦',
            Order::STATUS_CANCELLED => '❌',
            Order::STATUS_PENDING_PAYMENT => '⏳',
            Order::STATUS_PAID => '💳',
            Order::STATUS_PROCESSING => '⚙️',
            Order::STATUS_IN_PRODUCTION => '🏭',
            Order::STATUS_DESIGN_APPROVAL => '🎨',
            default => '📋',
        };
    }
}
