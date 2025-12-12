<?php

namespace Database\Seeders;

use App\Jobs\ProcessDigitalInvitations;
use App\Models\Address;
use App\Models\DigitalInvitation;
use App\Models\InvitationDetail;
use App\Models\Notification;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class RealisticTestSeeder extends Seeder
{
    /**
     * @var User[]
     */
    private array $customers = [];

    private ?Collection $digitalProducts = null;

    private ?Collection $physicalProducts = null;

    /**
     * Run the comprehensive test seeder.
     * This creates realistic data for testing ALL features:
     * - Multiple customers with addresses
     * - Orders in various statuses
     * - Digital invitations (draft, active, scheduled)
     * - Physical product orders
     * - Payments and reviews
     * - Notifications
     */
    public function run(): void
    {
        $this->command->info('🚀 Starting Realistic Test Data Seeder...');
        $this->command->newLine();

        DB::transaction(function () {
            // 1. Create test users
            $this->seedUsers();

            // 2. Get products (assumes products already seeded)
            $this->getProducts();

            // 3. Create orders with various scenarios
            $this->seedOrders();

            // 4. Create digital invitations
            $this->seedDigitalInvitations();

            // 5. Create reviews
            $this->seedReviews();

            // 6. Create notifications
            $this->seedNotifications();
        });

        $this->command->newLine();
        $this->command->info('✅ Realistic test data seeded successfully!');
        $this->printSummary();
    }

    /**
     * Create realistic test users (customers)
     */
    private function seedUsers(): void
    {
        $this->command->info('👥 Creating test users...');

        $testUsers = [
            [
                'full_name' => 'Jessica & David',
                'email' => 'jessica.david@wedding.com',
                'phone_number' => '081234001001',
                'address' => [
                    'street' => 'Jl. Asia Afrika No. 123',
                    'city' => 'Jakarta Selatan',
                    'state' => 'DKI Jakarta',
                    'subdistrict' => 'Kebayoran Baru',
                    'postal_code' => '12180',
                ],
            ],
            [
                'full_name' => 'Sarah & Michael',
                'email' => 'sarah.michael@gmail.com',
                'phone_number' => '082345002002',
                'address' => [
                    'street' => 'Jl. Dago No. 88',
                    'city' => 'Bandung',
                    'state' => 'Jawa Barat',
                    'subdistrict' => 'Coblong',
                    'postal_code' => '40135',
                ],
            ],
            [
                'full_name' => 'Rina & Ahmad',
                'email' => 'rina.ahmad@yahoo.com',
                'phone_number' => '083456003003',
                'address' => [
                    'street' => 'Jl. Malioboro No. 50',
                    'city' => 'Yogyakarta',
                    'state' => 'DI Yogyakarta',
                    'subdistrict' => 'Gedong Tengen',
                    'postal_code' => '55271',
                ],
            ],
            [
                'full_name' => 'Linda & Robert',
                'email' => 'linda.robert@outlook.com',
                'phone_number' => '084567004004',
                'address' => [
                    'street' => 'Jl. Tunjungan No. 99',
                    'city' => 'Surabaya',
                    'state' => 'Jawa Timur',
                    'subdistrict' => 'Genteng',
                    'postal_code' => '60275',
                ],
            ],
            [
                'full_name' => 'Maya & Doni',
                'email' => 'maya.doni@gmail.com',
                'phone_number' => '085678005005',
                'address' => [
                    'street' => 'Jl. Gajah Mada No. 77',
                    'city' => 'Semarang',
                    'state' => 'Jawa Tengah',
                    'subdistrict' => 'Semarang Tengah',
                    'postal_code' => '50134',
                ],
            ],
        ];

        foreach ($testUsers as $userData) {
            $user = User::firstOrCreate(
                ['email' => $userData['email']],
                [
                    'full_name' => $userData['full_name'],
                    'password' => Hash::make('password'),
                    'phone_number' => $userData['phone_number'],
                    'role' => 'customer',
                ]
            );

            // Create address
            Address::firstOrCreate(
                ['customer_id' => $user->id],
                array_merge($userData['address'], [
                    'customer_id' => $user->id,
                    'country' => 'Indonesia',
                ])
            );

            $this->customers[] = $user;
        }

        $this->command->info('   ✓ Created {count: '.count($this->customers).'} test customers');
    }

    /**
     * Get existing products (digital and physical)
     */
    private function getProducts(): void
    {
        $this->command->info('📦 Getting products...');

        $this->digitalProducts = Product::where('product_type', 'digital')
            ->whereNotNull('template_id')
            ->get();

        $this->physicalProducts = Product::where('product_type', 'physical')
            ->orWhereNull('product_type')
            ->get();

        $this->command->info('   ✓ Found {count: '.$this->digitalProducts->count().'} digital products');
        $this->command->info('   ✓ Found {count: '.$this->physicalProducts->count().'} physical products');

        if ($this->digitalProducts->isEmpty()) {
            $this->command->warn('   ⚠️  No digital products found. Run DigitalProductSeeder first.');
        }

        if ($this->physicalProducts->isEmpty()) {
            $this->command->warn('   ⚠️  No physical products found. Run ProductsTableSeeder first.');
        }
    }

    /**
     * Create orders with various scenarios
     */
    private function seedOrders(): void
    {
        $this->command->info('🛒 Creating realistic orders...');

        if (empty($this->customers)) {
            $this->command->error('   ✗ No customers found!');

            return;
        }

        // Scenario 1: Digital invitation order (Paid, ready to process)
        if ($this->digitalProducts && ! $this->digitalProducts->isEmpty()) {
            $this->createDigitalOrder(
                $this->customers[0], // Jessica & David
                $this->digitalProducts->take(2), // 2 digital invitations
                'Paid',
                now()->subDays(1),
                true // will trigger job
            );
        }

        // Scenario 2: Mixed order (digital + physical)
        if ($this->digitalProducts && ! $this->digitalProducts->isEmpty() && $this->physicalProducts && ! $this->physicalProducts->isEmpty()) {
            $this->createMixedOrder(
                $this->customers[1], // Sarah & Michael
                $this->digitalProducts->random(1),
                $this->physicalProducts->random(2),
                'Paid',
                now()->subDays(2)
            );
        }

        // Scenario 3: Physical product order - Processing
        if ($this->physicalProducts && ! $this->physicalProducts->isEmpty()) {
            $this->createPhysicalOrder(
                $this->customers[2], // Rina & Ahmad
                $this->physicalProducts->random(3),
                'Processing',
                now()->subDays(3)
            );
        }

        // Scenario 4: Physical product order - Shipped
        if ($this->physicalProducts && ! $this->physicalProducts->isEmpty()) {
            $this->createPhysicalOrder(
                $this->customers[3], // Linda & Robert
                $this->physicalProducts->random(2),
                'Shipped',
                now()->subDays(5)
            );
        }

        // Scenario 5: Completed order (old, ready for review)
        if ($this->physicalProducts && ! $this->physicalProducts->isEmpty()) {
            $this->createPhysicalOrder(
                $this->customers[4], // Maya & Doni
                $this->physicalProducts->random(1),
                'Completed',
                now()->subDays(30)
            );
        }

        // Scenario 6: Pending payment order
        if ($this->digitalProducts && ! $this->digitalProducts->isEmpty()) {
            $this->createDigitalOrder(
                $this->customers[0],
                $this->digitalProducts->random(1),
                'Pending Payment',
                now()->subHours(6),
                false // no payment yet
            );
        }

        $this->command->info('   ✓ Created realistic orders with various scenarios');
    }

    /**
     * Create digital invitation order
     *
     * @param  \Illuminate\Database\Eloquent\Collection|array  $products
     */
    private function createDigitalOrder(
        User $customer,
        $products,
        string $status,
        $createdAt,
        bool $createPayment = true
    ): Order {
        $orderNumber = 'DIS-'.strtoupper(uniqid());
        $totalAmount = $products->sum('base_price');

        $order = Order::create([
            'customer_id' => $customer->id,
            'order_number' => $orderNumber,
            'total_amount' => $totalAmount,
            'order_status' => $status,
            'shipping_address' => $customer->address->full_address ?? 'N/A - Digital Product',
            'shipping_cost' => 0, // Digital products have no shipping
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
            ]);
        }

        // Create payment
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
        $this->createInvitationDetail($order, $customer, $createdAt);

        $this->command->info("   ✓ Created digital order: {$orderNumber} ({$status})");

        return $order;
    }

    /**
     * Create mixed order (digital + physical)
     */
    private function createMixedOrder(
        User $customer,
        $digitalProducts,
        $physicalProducts,
        string $status,
        $createdAt
    ): Order {
        $orderNumber = 'MIX-'.strtoupper(uniqid());
        $totalAmount = $digitalProducts->sum('base_price') + $physicalProducts->sum('base_price');
        $shippingCost = 25000; // Physical items need shipping

        $order = Order::create([
            'customer_id' => $customer->id,
            'order_number' => $orderNumber,
            'total_amount' => $totalAmount + $shippingCost,
            'order_status' => $status,
            'shipping_address' => $customer->address->full_address ?? 'N/A',
            'shipping_cost' => $shippingCost,
            'shipping_service' => 'JNE REG',
            'courier' => 'JNE',
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);

        // Create order items
        foreach ($digitalProducts as $product) {
            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $product->id,
                'quantity' => 1,
                'unit_price' => $product->base_price,
            ]);
        }

        foreach ($physicalProducts as $product) {
            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $product->id,
                'quantity' => rand(1, 3),
                'unit_price' => $product->base_price,
            ]);
        }

        // Create payment
        Payment::create([
            'order_id' => $order->id,
            'transaction_id' => 'TRX-'.strtoupper(uniqid()),
            'payment_type' => 'full',
            'status' => 'Paid',
            'amount' => $totalAmount + $shippingCost,
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);

        // Create invitation details for digital products
        $this->createInvitationDetail($order, $customer, $createdAt);

        $this->command->info("   ✓ Created mixed order: {$orderNumber} ({$status})");

        return $order;
    }

    /**
     * Create physical product order
     */
    private function createPhysicalOrder(
        User $customer,
        $products,
        string $status,
        $createdAt
    ): Order {
        $orderNumber = 'PHY-'.strtoupper(uniqid());
        $totalAmount = $products->sum('base_price');
        $shippingCost = 30000;

        $order = Order::create([
            'customer_id' => $customer->id,
            'order_number' => $orderNumber,
            'total_amount' => $totalAmount + $shippingCost,
            'order_status' => $status,
            'shipping_address' => $customer->address->full_address ?? 'N/A',
            'shipping_cost' => $shippingCost,
            'shipping_service' => collect(['JNE REG', 'J&T Express', 'SiCepat REG'])->random(),
            'courier' => collect(['JNE', 'J&T', 'SiCepat'])->random(),
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);

        // Create order items
        foreach ($products as $product) {
            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $product->id,
                'quantity' => rand(1, 5),
                'unit_price' => $product->base_price,
            ]);
        }

        // Create payment
        Payment::create([
            'order_id' => $order->id,
            'transaction_id' => 'TRX-'.strtoupper(uniqid()),
            'payment_type' => 'full',
            'status' => 'Paid',
            'amount' => $totalAmount + $shippingCost,
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);

        $this->command->info("   ✓ Created physical order: {$orderNumber} ({$status})");

        return $order;
    }

    /**
     * Create invitation details (wedding data)
     */
    private function createInvitationDetail(Order $order, User $customer, $createdAt): void
    {
        $weddingData = $this->getRealisticWeddingData($customer);

        InvitationDetail::create([
            'order_id' => $order->id,
            'bride_full_name' => $weddingData['bride_full_name'],
            'bride_nickname' => $weddingData['bride_nickname'],
            'bride_parents' => $weddingData['bride_parents'],
            'groom_full_name' => $weddingData['groom_full_name'],
            'groom_nickname' => $weddingData['groom_nickname'],
            'groom_parents' => $weddingData['groom_parents'],
            'akad_date' => $weddingData['akad_date'],
            'akad_time' => $weddingData['akad_time'],
            'akad_location' => $weddingData['akad_location'],
            'reception_date' => $weddingData['reception_date'],
            'reception_time' => $weddingData['reception_time'],
            'reception_location' => $weddingData['reception_location'],
            'gmaps_link' => $weddingData['gmaps_link'],
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);
    }

    /**
     * Get realistic wedding data based on customer
     */
    private function getRealisticWeddingData(User $customer): array
    {
        $weddingDataMap = [
            'jessica.david@wedding.com' => [
                'bride_full_name' => 'Jessica Marie Anderson',
                'bride_nickname' => 'Jessica',
                'bride_parents' => 'Mr. Robert Anderson & Mrs. Linda Anderson',
                'groom_full_name' => 'David Michael Williams',
                'groom_nickname' => 'David',
                'groom_parents' => 'Mr. John Williams & Mrs. Sarah Williams',
                'akad_date' => '2025-12-31',
                'akad_time' => '10:00:00',
                'akad_location' => 'Masjid Al-Ikhlas, Jakarta Selatan',
                'reception_date' => '2025-12-31',
                'reception_time' => '18:00:00',
                'reception_location' => 'Grand Hyatt Hotel, Jakarta',
                'gmaps_link' => 'https://maps.google.com/?q=Grand+Hyatt+Jakarta',
            ],
            'sarah.michael@gmail.com' => [
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
            ],
        ];

        return $weddingDataMap[$customer->email] ?? [
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
            'reception_location' => 'Hotel Mulia Senayan, Jakarta',
            'gmaps_link' => 'https://maps.google.com/?q=Hotel+Mulia+Jakarta',
        ];
    }

    /**
     * Create digital invitations (process paid orders)
     */
    private function seedDigitalInvitations(): void
    {
        $this->command->info('💌 Processing digital invitations...');

        // Find paid orders with digital products that don't have invitations yet
        $paidOrders = Order::where('order_status', 'Paid')
            ->whereHas('items.product', fn ($q) => $q->where('product_type', 'digital'))
            ->whereDoesntHave('digitalInvitations')
            ->get();

        if ($paidOrders->isEmpty()) {
            $this->command->warn('   ⚠️  No paid digital orders found to process');

            return;
        }

        foreach ($paidOrders as $order) {
            try {
                // Dispatch the job (synchronously for seeding)
                ProcessDigitalInvitations::dispatchSync($order);
                $this->command->info("   ✓ Processed invitations for order: {$order->order_number}");
            } catch (\Exception $e) {
                $this->command->error("   ✗ Failed to process order {$order->order_number}: ".$e->getMessage());
            }
        }
    }

    /**
     * Create reviews for completed orders
     */
    private function seedReviews(): void
    {
        $this->command->info('⭐ Creating product reviews...');

        $completedOrders = Order::where('order_status', 'Completed')
            ->whereDoesntHave('items.product.reviews', fn ($q) => $q->whereIn('customer_id', $this->getCustomerIds()))
            ->with('items.product')
            ->get();

        if ($completedOrders->isEmpty()) {
            $this->command->warn('   ⚠️  No completed orders found for reviews');

            return;
        }

        $reviewCount = 0;
        foreach ($completedOrders as $order) {
            foreach ($order->items as $item) {
                Review::create([
                    'customer_id' => $order->customer_id,
                    'product_id' => $item->product_id,
                    'order_id' => $order->id,
                    'rating' => rand(4, 5),
                    'comment' => $this->getRandomReview(),
                    'is_verified' => true,
                    'created_at' => $order->created_at->addDays(rand(7, 14)),
                ]);
                $reviewCount++;
            }
        }

        $this->command->info("   ✓ Created {$reviewCount} product reviews");
    }

    /**
     * Create notifications
     */
    private function seedNotifications(): void
    {
        $this->command->info('🔔 Creating notifications...');

        // Digital invitation ready notifications are created by the job
        // Let's create some other notification types

        $notificationCount = Notification::where('type', 'digital_invitation_ready')->count();
        $this->command->info("   ✓ Found {$notificationCount} digital invitation notifications");

        // Add order status notifications
        $recentOrders = Order::where('created_at', '>', now()->subDays(7))->get();
        foreach ($recentOrders as $order) {
            if (! in_array($order->order_status, ['Pending Payment', 'Cancelled'])) {
                Notification::firstOrCreate([
                    'customer_id' => $order->customer_id,
                    'type' => 'order_status_update',
                    'data' => ['order_id' => $order->id],
                ], [
                    'title' => 'Pesanan Anda Diperbarui',
                    'message' => "Status pesanan {$order->order_number} telah diperbarui menjadi {$order->order_status}",
                    'created_at' => $order->updated_at,
                ]);
            }
        }

        $totalNotifications = Notification::count();
        $this->command->info("   ✓ Total notifications: {$totalNotifications}");
    }

    /**
     * Get customer IDs
     */
    private function getCustomerIds(): array
    {
        return collect($this->customers)->pluck('id')->toArray();
    }

    /**
     * Get random review text
     */
    private function getRandomReview(): string
    {
        $reviews = [
            'Produk sangat bagus dan berkualitas. Pengiriman cepat. Sangat puas!',
            'Hasil cetakan sangat memuaskan, warna tajam dan detail jelas. Terima kasih!',
            'Kualitas premium dengan harga terjangkau. Highly recommended!',
            'Pelayanan ramah dan responsif. Produk sesuai ekspektasi.',
            'Undangan digital sangat cantik dan mudah dikustomisasi. Love it!',
            'Packaging rapi, produk aman sampai tujuan. Kualitas sangat baik.',
            'Proses pemesanan mudah, hasilnya memuaskan. Akan order lagi!',
        ];

        return $reviews[array_rand($reviews)];
    }

    /**
     * Print summary
     */
    private function printSummary(): void
    {
        $this->command->newLine();
        $this->command->info('═══════════════════════════════════════════════════');
        $this->command->info('📊 Test Data Summary');
        $this->command->info('═══════════════════════════════════════════════════');

        $this->command->table(
            ['Entity', 'Count'],
            [
                ['Test Customers', count($this->customers)],
                ['Total Orders', Order::count()],
                ['Digital Invitations', DigitalInvitation::count()],
                ['Notifications', Notification::count()],
                ['Reviews', Review::count()],
            ]
        );

        $this->command->newLine();
        $this->command->info('🔑 Test User Credentials:');
        foreach ($this->customers as $customer) {
            $this->command->info("   📧 {$customer->email} / password");
        }

        $this->command->newLine();
        $this->command->info('📂 Order Statuses:');
        $statuses = Order::select('order_status', DB::raw('count(*) as count'))
            ->groupBy('order_status')
            ->get();

        foreach ($statuses as $status) {
            $this->command->info("   • {$status->order_status}: {$status->count}");
        }

        $this->command->newLine();
    }
}
