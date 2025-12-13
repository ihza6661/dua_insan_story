<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Order;
use App\Models\Product;
use App\Models\InvitationTemplate;
use App\Models\DigitalInvitation;
use App\Models\DigitalInvitationData;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Carbon\Carbon;

class DraftInvitationSeeder extends Seeder
{
    /**
     * Create draft digital invitations for testing scheduled activation feature.
     * 
     * This seeder creates draft invitations with various scheduled activation scenarios:
     * - Scheduled for tomorrow (24 hours)
     * - Scheduled for next week
     * - Scheduled for next month
     * - Overdue (should have been activated already)
     * - Draft without schedule
     */
    public function run(): void
    {
        $this->command->info('');
        $this->command->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->command->info('📝 Draft Invitation Test Data Seeder');
        $this->command->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->command->info('');

        // Get test customer
        $customer = User::where('email', 'customer@example.com')->first();
        
        if (!$customer) {
            $this->command->error('❌ Customer user not found. Please run UserSeeder first.');
            return;
        }

        // Get digital products
        $products = Product::where('product_type', 'digital')->get();
        
        if ($products->isEmpty()) {
            $this->command->error('❌ Digital products not found. Please run DigitalProductSeeder first.');
            return;
        }

        $sakenahProduct = $products->first();
        $classicProduct = $products->skip(1)->first() ?? $sakenahProduct;

        $this->command->info('📋 Prerequisites Check:');
        $this->command->info("   ✅ Customer: {$customer->email} (ID: {$customer->id})");
        $this->command->info("   ✅ Products Found: {$products->count()}");
        $this->command->info('');

        // Test Draft Invitations with Different Scenarios
        $draftInvitations = [
            [
                'product' => $sakenahProduct,
                'template_id' => $sakenahProduct->template_id,
                'slug' => 'scheduled-tomorrow-test',
                'order_number' => 'ORD-SCHEDULED-TOMORROW-' . time(),
                'scheduled_at' => Carbon::now()->addHours(24), // 24 hours from now (reminder should trigger)
                'scenario' => 'Scheduled for Tomorrow (Reminder Email)',
                'customization' => [
                    'bride_full_name' => 'Sarah Zahra binti Rahman',
                    'bride_nickname' => 'Sarah',
                    'bride_parents' => 'Bapak Rahman & Ibu Zahra',
                    'groom_full_name' => 'Ahmad Fauzi bin Yusuf',
                    'groom_nickname' => 'Ahmad',
                    'groom_parents' => 'Bapak Yusuf & Ibu Maryam',
                    'akad_date' => '2025-06-20',
                    'akad_time' => '09:00',
                    'akad_location' => 'Masjid Al-Hidayah, Jakarta',
                    'reception_date' => '2025-06-20',
                    'reception_time' => '11:00',
                    'reception_location' => 'Gedung Serbaguna, Jakarta',
                    'gmaps_link' => 'https://maps.google.com/?q=-6.2088,106.8456',
                    'primary_color' => '#2C5F2D',
                ],
            ],
            [
                'product' => $classicProduct,
                'template_id' => $classicProduct->template_id,
                'slug' => 'scheduled-next-week-test',
                'order_number' => 'ORD-SCHEDULED-WEEK-' . time(),
                'scheduled_at' => Carbon::now()->addWeek(), // 7 days from now
                'scenario' => 'Scheduled for Next Week',
                'customization' => [
                    'bride_name' => 'Maya Sari',
                    'bride_nickname' => 'Maya',
                    'groom_name' => 'Budi Santoso',
                    'groom_nickname' => 'Budi',
                    'event_date' => '2025-07-01',
                    'event_time' => '10:00',
                    'venue_name' => 'Grand Ballroom Hotel',
                    'venue_address' => 'Jl. Sudirman No. 1, Jakarta',
                    'venue_maps_url' => 'https://maps.google.com/?q=-6.2146,106.8451',
                    'theme_color' => '#B8860B',
                ],
            ],
            [
                'product' => $sakenahProduct,
                'template_id' => $sakenahProduct->template_id,
                'slug' => 'scheduled-next-month-test',
                'order_number' => 'ORD-SCHEDULED-MONTH-' . time(),
                'scheduled_at' => Carbon::now()->addMonth(), // 30 days from now
                'scenario' => 'Scheduled for Next Month',
                'customization' => [
                    'bride_full_name' => 'Rina Anggraini binti Sutanto',
                    'bride_nickname' => 'Rina',
                    'bride_parents' => 'Bapak Sutanto & Ibu Wati',
                    'groom_full_name' => 'Hendra Wijaya bin Bambang',
                    'groom_nickname' => 'Hendra',
                    'groom_parents' => 'Bapak Bambang & Ibu Sri',
                    'akad_date' => '2025-08-15',
                    'akad_time' => '08:00',
                    'akad_location' => 'Masjid Agung, Bandung',
                    'reception_date' => '2025-08-15',
                    'reception_time' => '12:00',
                    'reception_location' => 'Gedung Pernikahan, Bandung',
                    'gmaps_link' => 'https://maps.google.com/?q=-6.9175,107.6191',
                    'primary_color' => '#1B5E20',
                ],
            ],
            [
                'product' => $classicProduct,
                'template_id' => $classicProduct->template_id,
                'slug' => 'overdue-scheduled-test',
                'order_number' => 'ORD-OVERDUE-' . time(),
                'scheduled_at' => Carbon::now()->subHours(3), // 3 hours ago (OVERDUE - should be activated by cron)
                'scenario' => 'Overdue (Should Be Activated)',
                'customization' => [
                    'bride_name' => 'Dewi Lestari',
                    'bride_nickname' => 'Dewi',
                    'groom_name' => 'Andi Pratama',
                    'groom_nickname' => 'Andi',
                    'event_date' => '2025-06-10',
                    'event_time' => '09:00',
                    'venue_name' => 'Balai Kartini',
                    'venue_address' => 'Jl. Gatot Subroto, Jakarta',
                    'venue_maps_url' => 'https://maps.google.com/?q=-6.2615,106.7942',
                    'theme_color' => '#8B4513',
                ],
            ],
            [
                'product' => $sakenahProduct,
                'template_id' => $sakenahProduct->template_id,
                'slug' => 'draft-no-schedule-test',
                'order_number' => 'ORD-DRAFT-' . time(),
                'scheduled_at' => null, // No schedule set
                'scenario' => 'Draft Without Schedule',
                'customization' => [
                    'bride_full_name' => 'Laila Nur binti Hakim',
                    'bride_nickname' => 'Laila',
                    'bride_parents' => 'Bapak Hakim & Ibu Aisyah',
                    'groom_full_name' => 'Fahmi Yusuf bin Ibrahim',
                    'groom_nickname' => 'Fahmi',
                    'groom_parents' => 'Bapak Ibrahim & Ibu Fatma',
                    'akad_date' => '2025-09-05',
                    'akad_time' => '10:00',
                    'akad_location' => 'Masjid Raya, Surabaya',
                    'reception_date' => '2025-09-05',
                    'reception_time' => '13:00',
                    'reception_location' => 'Hotel Grand City, Surabaya',
                    'gmaps_link' => 'https://maps.google.com/?q=-7.2575,112.7521',
                    'primary_color' => '#4A148C',
                ],
            ],
            [
                'product' => $classicProduct,
                'template_id' => $classicProduct->template_id,
                'slug' => 'scheduled-3-hours-test',
                'order_number' => 'ORD-SCHEDULED-3H-' . time(),
                'scheduled_at' => Carbon::now()->addHours(3), // 3 hours from now
                'scenario' => 'Scheduled in 3 Hours (Urgent)',
                'customization' => [
                    'bride_name' => 'Siti Nurhaliza',
                    'bride_nickname' => 'Siti',
                    'groom_name' => 'Ridwan Kamil',
                    'groom_nickname' => 'Ridwan',
                    'event_date' => '2025-06-12',
                    'event_time' => '11:00',
                    'venue_name' => 'Gedung Pancasila',
                    'venue_address' => 'Jl. Thamrin No. 5, Jakarta',
                    'venue_maps_url' => 'https://maps.google.com/?q=-6.1944,106.8229',
                    'theme_color' => '#C62828',
                ],
            ],
        ];

        $created = 0;

        foreach ($draftInvitations as $index => $data) {
            $this->command->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
            $this->command->info("📝 Creating Draft Invitation #" . ($index + 1));
            $this->command->info("   Scenario: {$data['scenario']}");
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
            $this->command->info("      └─ Status: {$order->payment_status}");

            // Create Digital Invitation (DRAFT)
            $template = InvitationTemplate::find($data['template_id']);
            
            $invitation = DigitalInvitation::create([
                'user_id' => $customer->id,
                'order_id' => $order->id,
                'template_id' => $data['template_id'],
                'slug' => $data['slug'],
                'status' => 'draft', // DRAFT status
                'scheduled_activation_at' => $data['scheduled_at'], // Scheduled activation
                'activated_at' => null,
                'expires_at' => null,
                'view_count' => 0,
            ]);

            $scheduleInfo = $data['scheduled_at'] 
                ? $data['scheduled_at']->format('Y-m-d H:i:s') . " (" . $data['scheduled_at']->diffForHumans() . ")"
                : 'Not scheduled';

            $this->command->info("   ✅ Draft Invitation Created:");
            $this->command->info("      ├─ Invitation ID: {$invitation->id}");
            $this->command->info("      ├─ Slug: {$invitation->slug}");
            $this->command->info("      ├─ Status: {$invitation->status}");
            $this->command->info("      ├─ Template: {$template->name}");
            $this->command->info("      ├─ Scheduled At: {$scheduleInfo}");
            $this->command->info("      └─ Preview URL: " . config('app.frontend_url') . "/my-invitations?preview={$invitation->id}");

            // Create Customization Data
            DigitalInvitationData::create([
                'digital_invitation_id' => $invitation->id,
                'customization_json' => [
                    'custom_fields' => $data['customization'],
                ],
            ]);

            $coupleInfo = $this->getCoupleNames($data['customization']);
            $this->command->info("   ✅ Customization Created:");
            $this->command->info("      ├─ Couple: {$coupleInfo}");
            $this->command->info("      └─ Fields: " . count($data['customization']) . " fields configured");

            $created++;
            $this->command->info('');
        }

        // Summary
        $this->command->info('');
        $this->command->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->command->info('📊 Seeding Summary');
        $this->command->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->command->info("   ✅ Draft Invitations Created: {$created}");
        
        $scheduled = DigitalInvitation::where('status', 'draft')
            ->whereNotNull('scheduled_activation_at')
            ->count();
        $overdue = DigitalInvitation::where('status', 'draft')
            ->whereNotNull('scheduled_activation_at')
            ->where('scheduled_activation_at', '<', Carbon::now())
            ->count();
        
        $this->command->info("   📅 Scheduled: {$scheduled}");
        $this->command->info("   ⚠️  Overdue: {$overdue}");
        $this->command->info("   📦 Total Draft: " . DigitalInvitation::where('status', 'draft')->count());
        $this->command->info("   👤 Customer: {$customer->email}");
        $this->command->info('');
        
        // Display Test Scenarios
        $this->command->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->command->info('🧪 Test Scenarios Created');
        $this->command->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        
        foreach (DigitalInvitation::where('status', 'draft')->with('template')->get() as $inv) {
            $scheduleText = $inv->scheduled_activation_at 
                ? $inv->scheduled_activation_at->diffForHumans() 
                : 'No schedule';
            $isOverdue = $inv->scheduled_activation_at && $inv->scheduled_activation_at->isPast();
            $statusEmoji = $isOverdue ? '⚠️ ' : ($inv->scheduled_activation_at ? '📅' : '📝');
            
            $this->command->info("   {$statusEmoji} {$inv->slug}:");
            $this->command->info("      ├─ Template: {$inv->template->name}");
            $this->command->info("      └─ {$scheduleText}");
        }
        
        $this->command->info('');
        $this->command->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->command->info('🧪 Testing Instructions');
        $this->command->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->command->info('   1. Test scheduled activation command:');
        $this->command->info('      php artisan invitations:activate-scheduled');
        $this->command->info('      ➜ Should activate overdue invitations');
        $this->command->info('');
        $this->command->info('   2. Test reminder email command:');
        $this->command->info('      php artisan invitations:send-reminders');
        $this->command->info('      ➜ Should send reminder for 24h scheduled invitations');
        $this->command->info('');
        $this->command->info('   3. View admin scheduled page:');
        $this->command->info('      http://localhost:3000/admin/undangan-digital/terjadwal');
        $this->command->info('      ➜ See all scheduled invitations with countdown');
        $this->command->info('');
        $this->command->info('   4. Test scheduled API endpoint:');
        $this->command->info('      GET /api/v1/admin/digital-invitations/scheduled');
        $this->command->info('      ➜ Returns scheduled invitations with metadata');
        $this->command->info('');
        $this->command->info('   5. Test preview API endpoint:');
        $this->command->info('      GET /api/v1/digital-invitations/{id}/preview');
        $this->command->info('      ➜ Preview draft without incrementing view count');
        $this->command->info('');
        $this->command->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->command->info('');
        $this->command->info('🎉 Draft invitation test data seeding completed successfully!');
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
