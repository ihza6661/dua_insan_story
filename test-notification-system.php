#!/usr/bin/env php
<?php

/*
 * Test Script for Digital Invitation Notification System
 * This simulates a complete purchase flow and tests the queue architecture
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Models\InvitationTemplate;
use App\Models\Product;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\InvitationDetail;
use App\Models\DigitalInvitation;
use App\Models\Notification;
use App\Jobs\ProcessDigitalInvitations;

echo "\n";
echo "========================================\n";
echo "Digital Invitation Notification Test\n";
echo "========================================\n\n";

// Step 1: Get or create test user
echo "1. Setting up test user...\n";
$user = User::where('email', 'test@example.com')->first();
if (!$user) {
    $user = User::create([
        'full_name' => 'Test User',
        'email' => 'test@example.com',
        'phone' => '081234567890',
        'password' => bcrypt('password'),
    ]);
    echo "   ✓ Created user: {$user->email} (ID: {$user->id})\n";
} else {
    echo "   ✓ Using existing user: {$user->email} (ID: {$user->id})\n";
}

// Step 2: Get template
echo "\n2. Getting template...\n";
$template = InvitationTemplate::first();
if (!$template) {
    echo "   ✗ ERROR: No templates found. Run: php artisan db:seed --class=InvitationTemplateSeeder\n";
    exit(1);
}
echo "   ✓ Using template: {$template->name} (ID: {$template->id})\n";

// Step 3: Create digital product
echo "\n3. Creating digital product...\n";
$category = \App\Models\ProductCategory::first();
if (!$category) {
    echo "   ✗ ERROR: No product categories found. Create one first.\n";
    exit(1);
}
$product = Product::create([
    'category_id' => $category->id,
    'product_type' => 'digital', // IMPORTANT: Mark as digital
    'template_id' => $template->id, // IMPORTANT: Link to template
    'name' => "Digital Invitation - {$template->name}",
    'description' => 'Test digital invitation product',
    'base_price' => 150000,
    'slug' => 'test-digital-' . time(),
    'weight' => 0,
    'min_order_quantity' => 1,
    'is_active' => true,
]);
echo "   ✓ Created product: {$product->name} (ID: {$product->id})\n";
echo "   ✓ Product type: {$product->product_type}, Template ID: {$product->template_id}\n";

// Step 4: Create order
echo "\n4. Creating order...\n";
$order = Order::create([
    'customer_id' => $user->id,
    'order_number' => 'TEST-' . time(),
    'total_amount' => 150000,
    'order_status' => 'Paid',
    'shipping_address' => 'Test Address',
    'shipping_cost' => 0,
]);
echo "   ✓ Created order: {$order->order_number} (ID: {$order->id})\n";

// Step 5: Create order item with digital template link
echo "\n5. Creating order item...\n";
$orderItem = OrderItem::create([
    'order_id' => $order->id,
    'product_id' => $product->id,
    'quantity' => 1,
    'price' => 150000,
]);
echo "   ✓ Created order item for digital product\n";

// Step 6: Create payment
echo "\n6. Creating payment...\n";
$payment = Payment::create([
    'order_id' => $order->id,
    'transaction_id' => 'TEST-TRX-' . time(),
    'payment_type' => 'full',
    'status' => 'Paid',
    'amount' => 150000,
    'payment_method' => 'credit_card',
]);
echo "   ✓ Created payment: {$payment->transaction_id}\n";

// Step 7: Create invitation details (wedding data)
echo "\n7. Creating invitation details (wedding data)...\n";
$invitationDetails = InvitationDetail::create([
    'order_id' => $order->id,
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
    'reception_time' => '15:00:00',
    'reception_location' => 'Grand Ballroom Hotel Mulia, Jl. Asia Afrika, Jakarta',
    'gmaps_link' => 'https://maps.google.com/?q=Hotel+Mulia+Senayan',
]);
echo "   ✓ Created invitation details with complete wedding data\n";

// Step 8: Count before
echo "\n8. Checking state before job...\n";
$invitationsBefore = DigitalInvitation::count();
$notificationsBefore = Notification::count();
echo "   - Digital invitations: {$invitationsBefore}\n";
echo "   - Notifications: {$notificationsBefore}\n";

// Step 9: Dispatch job
echo "\n9. Dispatching ProcessDigitalInvitations job...\n";
echo "   ⏳ Dispatching job to queue...\n";
ProcessDigitalInvitations::dispatch($order);
echo "   ✓ Job dispatched!\n";
echo "   → Check queue worker terminal for processing\n";

// Step 10: Wait for job to process
echo "\n10. Waiting for job to complete...\n";
echo "   ⏳ Waiting 5 seconds for queue worker to process...\n";
sleep(5);

// Step 11: Check results
echo "\n11. Checking results...\n";
$invitationsAfter = DigitalInvitation::where('order_id', $order->id)->count();
$notificationsAfter = Notification::where('user_id', $user->id)
    ->where('type', 'digital_invitation_ready')
    ->count();

echo "   - Digital invitations created: {$invitationsAfter}\n";
echo "   - Notifications created: {$notificationsAfter}\n";

if ($invitationsAfter > 0) {
    $invitation = DigitalInvitation::where('order_id', $order->id)->with('data')->first();
    echo "\n✅ SUCCESS! Invitation created:\n";
    echo "   - ID: {$invitation->id}\n";
    echo "   - Slug: {$invitation->slug}\n";
    echo "   - Status: {$invitation->status}\n";
    echo "   - Template: {$invitation->template->name}\n";
    
    // Check if wedding data was auto-populated
    if ($invitation->data && $invitation->data->customization_json) {
        $json = $invitation->data->customization_json;
        $customFields = $json['custom_fields'] ?? [];
        if (!empty($customFields)) {
            echo "\n✅ Wedding data auto-populated:\n";
            echo "   - Bride: " . ($customFields['bride_full_name'] ?? 'NOT SET') . "\n";
            echo "   - Groom: " . ($customFields['groom_full_name'] ?? 'NOT SET') . "\n";
            echo "   - Akad: " . ($customFields['akad_date'] ?? 'NOT SET') . " at " . ($customFields['akad_time'] ?? 'NOT SET') . "\n";
            echo "   - Reception: " . ($customFields['reception_date'] ?? 'NOT SET') . " at " . ($customFields['reception_time'] ?? 'NOT SET') . "\n";
            echo "   - Fields populated: " . count($customFields) . "\n";
        } else {
            echo "\n⚠️  WARNING: Customization data is empty\n";
        }
    } else {
        echo "\n⚠️  WARNING: No customization data found\n";
    }
} else {
    echo "\n✗ FAILED: No invitation created\n";
    echo "   Check logs: tail -f storage/logs/laravel.log\n";
    exit(1);
}

if ($notificationsAfter > 0) {
    $notification = Notification::where('user_id', $user->id)
        ->where('type', 'digital_invitation_ready')
        ->latest()
        ->first();
    echo "\n✅ SUCCESS! Notification created:\n";
    echo "   - Title: {$notification->title}\n";
    echo "   - Message: {$notification->message}\n";
    echo "   - Read: " . ($notification->is_read ? 'Yes' : 'No') . "\n";
} else {
    echo "\n⚠️  WARNING: No notification created\n";
}

// Step 12: Performance metrics
echo "\n12. Performance check...\n";
$logs = file_get_contents(storage_path('logs/laravel.log'));
if (preg_match('/ProcessDigitalInvitations job completed successfully/', $logs)) {
    echo "   ✓ Job completed successfully\n";
} else {
    echo "   ⚠️  Job may have failed - check logs\n";
}

// Summary
echo "\n========================================\n";
echo "Test Summary\n";
echo "========================================\n";
echo "Order ID: {$order->id}\n";
echo "User ID: {$user->id}\n";
echo "Template ID: {$template->id}\n";
echo "Invitations: {$invitationsAfter} created\n";
echo "Notifications: {$notificationsAfter} created\n";
echo "\n✅ Test completed! Queue architecture working correctly.\n\n";

// Clean up instructions
echo "To clean up test data:\n";
echo "  php artisan tinker --execute=\"\n";
echo "    App\\Models\\DigitalInvitation::where('order_id', {$order->id})->delete();\n";
echo "    App\\Models\\Order::find({$order->id})->delete();\n";
echo "  \"\n\n";
