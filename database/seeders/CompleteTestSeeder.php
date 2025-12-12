<?php

namespace Database\Seeders;

use App\Jobs\ProcessDigitalInvitations;
use App\Models\InvitationDetail;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Complete Test Seeder - Seeds all data needed for comprehensive testing
 *
 * Usage: php artisan db:seed --class=CompleteTestSeeder
 *
 * This seeder:
 * 1. Calls all existing seeders (users, products, templates, etc.)
 * 2. Creates additional digital invitation test scenarios
 * 3. Processes digital invitations through the queue
 */
class CompleteTestSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('');
        $this->command->info('═══════════════════════════════════════════════════');
        $this->command->info('🚀 Complete Test Data Seeder');
        $this->command->info('═══════════════════════════════════════════════════');
        $this->command->newLine();

        // Step 1: Run all base seeders
        $this->command->info('📦 Step 1: Seeding base data...');
        $this->call([
            UserSeeder::class,
            AdminUserSeeder::class,
            ProductCategorySeeder::class,
            InvitationTemplateSeeder::class,
            TemplateFieldSeeder::class,
            ProductsTableSeeder::class,
            ProductVariantsTableSeeder::class, // Add variants
            DigitalProductSeeder::class,
            ProductImageSeeder::class, // Now it will work
            ComprehensiveOrderSeeder::class, // Add comprehensive orders
        ]);

        // Step 2: Create test digital invitation orders
        $this->command->newLine();
        $this->command->info('💌 Step 2: Creating digital invitation orders...');
        $this->seedDigitalOrders();

        // Step 3: Process digital invitations
        $this->command->newLine();
        $this->command->info('⚡ Step 3: Processing digital invitations...');
        $this->processDigitalInvitations();

        // Print summary
        $this->command->newLine();
        $this->printSummary();
    }

    /**
     * Create digital invitation orders with various scenarios
     */
    private function seedDigitalOrders(): void
    {
        $customer = User::where('email', 'customer@example.com')->first();

        if (! $customer) {
            $this->command->error('   ✗ Customer not found! UserSeeder may have failed.');

            return;
        }

        $digitalProducts = Product::where('product_type', 'digital')
            ->whereNotNull('template_id')
            ->get();

        if ($digitalProducts->isEmpty()) {
            $this->command->warn('   ⚠️  No digital products found!');

            return;
        }

        // Scenario 1: Single digital invitation (Paid, ready to process)
        $order1 = $this->createDigitalOrder(
            $customer,
            $digitalProducts->first(),
            'Paid',
            now()->subDays(1),
            [
                'bride_full_name' => 'Jessica Marie Anderson',
                'bride_nickname' => 'Jessica',
                'bride_parents' => 'Mr. Robert Anderson & Mrs. Linda Anderson',
                'groom_full_name' => 'David Michael Williams',
                'groom_nickname' => 'David',
                'groom_parents' => 'Mr. John Williams & Mrs. Sarah Williams',
                'akad_date' => '2025-12-31',
                'akad_time' => '10:00:00',
                'akad_location' => 'Masjid Al-Ikhlas, Jakarta',
                'reception_date' => '2025-12-31',
                'reception_time' => '18:00:00',
                'reception_location' => 'Grand Hyatt Hotel, Jakarta',
                'gmaps_link' => 'https://maps.google.com/?q=Grand+Hyatt+Jakarta',
            ]
        );
        $this->command->info("   ✅ Created order: {$order1->order_number} (Paid, single invitation)");

        // Scenario 2: Multiple digital invitations (Paid, ready to process)
        if ($digitalProducts->count() >= 2) {
            $order2 = $this->createDigitalOrder(
                $customer,
                $digitalProducts->take(2),
                'Paid',
                now()->subDays(2),
                [
                    'bride_full_name' => 'Sarah Elizabeth Brown',
                    'bride_nickname' => 'Sarah',
                    'bride_parents' => 'Mr. Thomas Brown & Mrs. Emma Brown',
                    'groom_full_name' => 'Michael James Wilson',
                    'groom_nickname' => 'Michael',
                    'groom_parents' => 'Mr. Robert Wilson & Mrs. Jennifer Wilson',
                    'akad_date' => '2026-06-15',
                    'akad_time' => '09:00:00',
                    'akad_location' => 'Masjid Agung, Bandung',
                    'reception_date' => '2026-06-15',
                    'reception_time' => '17:00:00',
                    'reception_location' => 'Trans Luxury Hotel, Bandung',
                    'gmaps_link' => 'https://maps.google.com/?q=Trans+Luxury+Hotel+Bandung',
                ]
            );
            $this->command->info("   ✅ Created order: {$order2->order_number} (Paid, multiple invitations)");
        }

        // Scenario 3: Pending payment order (not yet paid)
        $order3 = $this->createDigitalOrder(
            $customer,
            $digitalProducts->random(1)->first(),
            'Pending Payment',
            now()->subHours(6),
            [
                'bride_full_name' => 'Siti Nurhaliza',
                'bride_nickname' => 'Siti',
                'bride_parents' => 'Mr. Ahmad & Mrs. Fatimah',
                'groom_full_name' => 'Muhammad Rizki',
                'groom_nickname' => 'Rizki',
                'groom_parents' => 'Mr. Budi & Mrs. Ani',
                'akad_date' => '2026-03-20',
                'akad_time' => '08:00:00',
                'akad_location' => 'Masjid Raya',
                'reception_date' => '2026-03-20',
                'reception_time' => '19:00:00',
                'reception_location' => 'Hotel Mulia, Jakarta',
                'gmaps_link' => 'https://maps.google.com/?q=Hotel+Mulia+Jakarta',
            ],
            false // no payment
        );
        $this->command->info("   ✅ Created order: {$order3->order_number} (Pending Payment)");
    }

    /**
     * Create a digital invitation order
     *
     * @param  Product|\Illuminate\Database\Eloquent\Collection  $products
     */
    private function createDigitalOrder(
        User $customer,
        $products,
        string $status,
        $createdAt,
        array $weddingData,
        bool $createPayment = true
    ): Order {
        // Handle single product or collection
        if (! $products instanceof \Illuminate\Database\Eloquent\Collection) {
            $products = collect([$products]);
        }

        $orderNumber = 'DIS-'.strtoupper(uniqid());
        $totalAmount = $products->sum('base_price');

        $order = Order::create([
            'customer_id' => $customer->id,
            'order_number' => $orderNumber,
            'total_amount' => $totalAmount,
            'order_status' => $status,
            'shipping_address' => 'N/A - Digital Product',
            'shipping_cost' => 0,
            'payment_gateway' => 'midtrans',
            'payment_status' => $createPayment ? 'paid' : 'pending',
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);

        // Create order items
        foreach ($products as $product) {
            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $product->id,
                'quantity' => 1,
                'unit_price' => $product->base_price,
                'sub_total' => $product->base_price,
            ]);
        }

        // Create payment if needed
        if ($createPayment) {
            Payment::create([
                'order_id' => $order->id,
                'transaction_id' => 'TRX-'.strtoupper(uniqid()),
                'payment_type' => 'full',
                'status' => 'Paid',
                'amount' => $totalAmount,
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);
        }

        // Create invitation details (wedding data)
        InvitationDetail::create(array_merge([
            'order_id' => $order->id,
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ], $weddingData));

        return $order;
    }

    /**
     * Process all paid digital orders through the queue
     */
    private function processDigitalInvitations(): void
    {
        $paidOrders = Order::where('order_status', 'Paid')
            ->whereHas('items.product', fn ($q) => $q->where('product_type', 'digital'))
            ->whereDoesntHave('digitalInvitations')
            ->get();

        if ($paidOrders->isEmpty()) {
            $this->command->warn('   ⚠️  No paid digital orders to process');

            return;
        }

        foreach ($paidOrders as $order) {
            try {
                // Process synchronously for seeding (so we can see results immediately)
                ProcessDigitalInvitations::dispatchSync($order);
                $invitationCount = $order->digitalInvitations()->count();
                $this->command->info("   ✅ Processed {$order->order_number}: {$invitationCount} invitation(s) created");
            } catch (\Exception $e) {
                $this->command->error("   ✗ Failed to process {$order->order_number}: ".$e->getMessage());
            }
        }
    }

    /**
     * Print summary of seeded data
     */
    private function printSummary(): void
    {
        $this->command->info('═══════════════════════════════════════════════════');
        $this->command->info('📊 Seeding Summary');
        $this->command->info('═══════════════════════════════════════════════════');

        $users = User::count();
        $products = Product::count();
        $digitalProducts = Product::where('product_type', 'digital')->count();
        $orders = Order::count();
        $invitations = \App\Models\DigitalInvitation::count();
        $notifications = \App\Models\Notification::count();

        $this->command->table(
            ['Entity', 'Count'],
            [
                ['Users', $users],
                ['Products (Total)', $products],
                ['Digital Products', $digitalProducts],
                ['Orders', $orders],
                ['Digital Invitations', $invitations],
                ['Notifications', $notifications],
            ]
        );

        $this->command->newLine();
        $this->command->info('🔑 Test Credentials:');
        $this->command->info('   Customer: customer@example.com / password');
        $this->command->info('   Admin: admin@duainsan.story / password');

        $this->command->newLine();
        $this->command->info('✅ All test data seeded successfully!');
        $this->command->info('   You can now test all features of the application.');
        $this->command->newLine();
    }
}
