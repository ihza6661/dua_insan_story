<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Order;
use App\Models\Product;
use App\Models\InvitationTemplate;
use App\Models\DigitalInvitation;
use App\Models\DigitalInvitationData;
use App\Mail\DigitalInvitationReady;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class DigitalInvitationTestSeeder extends Seeder
{
    /**
     * Create test digital invitations with complete order flow.
     * 
     * This seeder creates realistic test data for digital invitation testing,
     * including orders, invitations, customizations, and email notifications.
     */
    public function run(): void
    {
        $this->command->info('');
        $this->command->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->command->info('🎨 Digital Invitation Test Data Seeder');
        $this->command->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->command->info('');

        // Get test customer
        $customer = User::where('email', 'customer@example.com')->first();
        
        if (!$customer) {
            $this->command->error('❌ Customer user not found. Please run UserSeeder first.');
            return;
        }

        // Get digital products
        $sakenahProduct = Product::where('product_type', 'digital')
            ->where('template_id', 1) // Sakeenah template
            ->first();
            
        $classicProduct = Product::where('product_type', 'digital')
            ->where('template_id', 2) // Classic template
            ->first();

        if (!$sakenahProduct || !$classicProduct) {
            $this->command->error('❌ Digital products not found. Please run DigitalProductSeeder first.');
            return;
        }

        $this->command->info('📋 Prerequisites Check:');
        $this->command->info("   ✅ Customer: {$customer->email} (ID: {$customer->id})");
        $this->command->info("   ✅ Sakeenah Product: {$sakenahProduct->name} (ID: {$sakenahProduct->id})");
        $this->command->info("   ✅ Classic Product: {$classicProduct->name} (ID: {$classicProduct->id})");
        $this->command->info('');

        // Test Data Sets
        $testInvitations = [
            [
                'product' => $sakenahProduct,
                'template_id' => 1,
                'slug' => 'rizki-aisyah-wedding',
                'order_number' => 'ORD-SAKEENAH-' . time(),
                'customization' => [
                    'bride_full_name' => 'Siti Aisyah binti Abdullah',
                    'bride_nickname' => 'Aisyah',
                    'bride_parents' => 'Bapak Abdullah & Ibu Fatimah',
                    'groom_full_name' => 'Muhammad Rizki bin Ahmad',
                    'groom_nickname' => 'Rizki',
                    'groom_parents' => 'Bapak Ahmad & Ibu Siti',
                    'akad_date' => '2025-06-15',
                    'akad_time' => '09:00',
                    'akad_location' => 'Masjid Al-Ikhlas, Jl. Merdeka No. 123, Jakarta Selatan',
                    'reception_date' => '2025-06-15',
                    'reception_time' => '11:00',
                    'reception_location' => 'Gedung Serbaguna Al-Barokah, Jl. Kemerdekaan No. 456, Jakarta Selatan',
                    'gmaps_link' => 'https://maps.google.com/?q=-6.2088,106.8456',
                    'prewedding_photo' => 'https://via.placeholder.com/800x600/2C5F2D/FFFFFF?text=Rizki+%26+Aisyah',
                    'primary_color' => '#2C5F2D',
                ],
            ],
            [
                'product' => $classicProduct,
                'template_id' => 2,
                'slug' => 'dimas-putri-wedding',
                'order_number' => 'ORD-CLASSIC-' . time(),
                'customization' => [
                    'bride_name' => 'Putri Rahayu',
                    'bride_nickname' => 'Putri',
                    'groom_name' => 'Dimas Prasetyo',
                    'groom_nickname' => 'Dimas',
                    'event_date' => '2025-07-20',
                    'event_time' => '10:00',
                    'venue_name' => 'Grand Ballroom Hotel Mulia',
                    'venue_address' => 'Jl. Asia Afrika No. 8, Jakarta Pusat',
                    'venue_maps_url' => 'https://maps.google.com/?q=-6.2146,106.8451',
                    'additional_info' => 'Dresscode: Formal. No kids policy.',
                    'couple_photo' => 'https://via.placeholder.com/800x600/B8860B/FFFFFF?text=Dimas+%26+Putri',
                    'theme_color' => '#B8860B',
                ],
            ],
            [
                'product' => $sakenahProduct,
                'template_id' => 1,
                'slug' => 'fajar-nina-wedding',
                'order_number' => 'ORD-SAKEENAH2-' . time(),
                'customization' => [
                    'bride_full_name' => 'Nina Marlina binti Sukarno',
                    'bride_nickname' => 'Nina',
                    'bride_parents' => 'Bapak Sukarno & Ibu Dewi',
                    'groom_full_name' => 'Fajar Ramadhan bin Hasan',
                    'groom_nickname' => 'Fajar',
                    'groom_parents' => 'Bapak Hasan & Ibu Ratna',
                    'akad_date' => '2025-08-10',
                    'akad_time' => '08:00',
                    'akad_location' => 'Masjid Agung Al-Azhar, Jakarta Selatan',
                    'reception_date' => '2025-08-10',
                    'reception_time' => '12:00',
                    'reception_location' => 'Balai Sudirman, Jakarta Pusat',
                    'gmaps_link' => 'https://maps.google.com/?q=-6.2615,106.7942',
                    'prewedding_photo' => 'https://via.placeholder.com/800x600/1B5E20/FFFFFF?text=Fajar+%26+Nina',
                    'primary_color' => '#1B5E20',
                ],
            ],
        ];

        $created = 0;
        $emailsQueued = 0;

        foreach ($testInvitations as $index => $data) {
            $this->command->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
            $this->command->info("📝 Creating Test Invitation #" . ($index + 1));
            $this->command->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");

            // Check if invitation with this slug already exists
            $existingInvitation = DigitalInvitation::where('slug', $data['slug'])->first();
            if ($existingInvitation) {
                $this->command->warn("   ⏭️  Skipped: Invitation '{$data['slug']}' already exists");
                continue;
            }

            // Create Order
            $order = Order::create([
                'customer_id' => $customer->id,
                'order_number' => $data['order_number'],
                'total_amount' => $data['product']->base_price,
                'subtotal_amount' => $data['product']->base_price,
                'discount_amount' => 0,
                'shipping_cost' => 0,
                'shipping_method' => null,
                'shipping_address' => null,
                'payment_gateway' => 'midtrans',
                'payment_status' => 'PAID',
                'order_status' => 'PAID',
                'snap_token' => 'test-' . Str::random(32),
            ]);

            $this->command->info("   ✅ Order Created:");
            $this->command->info("      ├─ Order Number: {$order->order_number}");
            $this->command->info("      ├─ Order ID: {$order->id}");
            $this->command->info("      ├─ Status: {$order->payment_status}");
            $this->command->info("      └─ Amount: Rp " . number_format($order->total_amount, 0, ',', '.'));

            // Create Digital Invitation
            $template = InvitationTemplate::find($data['template_id']);
            
            $invitation = DigitalInvitation::create([
                'user_id' => $customer->id,
                'order_id' => $order->id,
                'template_id' => $data['template_id'],
                'slug' => $data['slug'],
                'status' => 'active',
                'activated_at' => now(),
                'expires_at' => now()->addMonths(12),
                'view_count' => 0,
            ]);

            $this->command->info("   ✅ Digital Invitation Created:");
            $this->command->info("      ├─ Invitation ID: {$invitation->id}");
            $this->command->info("      ├─ Slug: {$invitation->slug}");
            $this->command->info("      ├─ Status: {$invitation->status}");
            $this->command->info("      ├─ Template: {$template->name}");
            $this->command->info("      ├─ Public URL: {$invitation->public_url}");
            $this->command->info("      ├─ Activated: {$invitation->activated_at->format('Y-m-d H:i:s')}");
            $this->command->info("      └─ Expires: {$invitation->expires_at->format('Y-m-d H:i:s')}");

            // Create Customization Data
            $customizationData = DigitalInvitationData::create([
                'digital_invitation_id' => $invitation->id,
                'customization_json' => [
                    'custom_fields' => $data['customization'],
                ],
            ]);

            $coupleInfo = $this->getCoupleNames($data['customization']);
            $this->command->info("   ✅ Customization Created:");
            $this->command->info("      ├─ Customization ID: {$customizationData->id}");
            $this->command->info("      ├─ Couple: {$coupleInfo}");
            $this->command->info("      └─ Fields: " . count($data['customization']) . " fields configured");

            // Queue Email Notification
            try {
                $editUrl = config('app.frontend_url') . '/my-invitations/' . $invitation->id . '/edit';
                
                Mail::to($customer->email)->queue(
                    new DigitalInvitationReady($invitation, $customer, $template, $editUrl)
                );

                $this->command->info("   ✅ Email Queued:");
                $this->command->info("      ├─ To: {$customer->email}");
                $this->command->info("      ├─ Template: DigitalInvitationReady");
                $this->command->info("      └─ Edit URL: {$editUrl}");
                
                $emailsQueued++;
            } catch (\Exception $e) {
                $this->command->warn("   ⚠️  Email Queue Failed: " . $e->getMessage());
            }

            $created++;
            $this->command->info('');
        }

        // Summary
        $this->command->info('');
        $this->command->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->command->info('📊 Seeding Summary');
        $this->command->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->command->info("   ✅ Digital Invitations Created: {$created}");
        $this->command->info("   📧 Emails Queued: {$emailsQueued}");
        $this->command->info("   📦 Total Invitations: " . DigitalInvitation::count());
        $this->command->info("   👤 Customer: {$customer->email}");
        $this->command->info('');
        
        // Display Test URLs
        $this->command->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->command->info('🔗 Test URLs');
        $this->command->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        
        foreach (DigitalInvitation::where('status', 'active')->get() as $inv) {
            $this->command->info("   📱 {$inv->slug}:");
            $this->command->info("      └─ {$inv->public_url}");
        }
        
        $this->command->info('');
        $this->command->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->command->info('🧪 Testing Instructions');
        $this->command->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->command->info('   1. Process email queue:');
        $this->command->info('      php artisan queue:work --once');
        $this->command->info('');
        $this->command->info('   2. Login to frontend:');
        $this->command->info('      URL: http://localhost:8080/login');
        $this->command->info("      Email: {$customer->email}");
        $this->command->info('      Password: password');
        $this->command->info('');
        $this->command->info('   3. View invitations:');
        $this->command->info('      http://localhost:8080/my-invitations');
        $this->command->info('');
        $this->command->info('   4. Test API:');
        $this->command->info('      GET /api/v1/digital-invitations');
        $this->command->info('');
        $this->command->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->command->info('');
        $this->command->info('🎉 Digital invitation test data seeding completed successfully!');
        $this->command->info('');
    }

    /**
     * Extract couple names from customization data
     */
    private function getCoupleNames(array $customization): string
    {
        if (isset($customization['bride_nickname']) && isset($customization['groom_nickname'])) {
            return "{$customization['groom_nickname']} & {$customization['bride_nickname']}";
        }
        
        if (isset($customization['groom_name']) && isset($customization['bride_name'])) {
            $groomFirst = explode(' ', $customization['groom_name'])[0];
            $brideFirst = explode(' ', $customization['bride_name'])[0];
            return "{$groomFirst} & {$brideFirst}";
        }
        
        return "Test Couple";
    }
}
