#!/usr/bin/env php
<?php

/*
 * Test Multiple Digital Invitations in One Order
 * Tests that the queue can handle multiple digital products
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
echo "Multiple Digital Invitations Test\n";
echo "========================================\n\n";

// Get test user
echo "1. Setting up test user...\n";
$user = User::where('email', 'test@example.com')->first();
if (!$user) {
    echo "   ✗ ERROR: Test user not found. Run test-notification-system.php first\n";
    exit(1);
}
echo "   ✓ Using user: {$user->email} (ID: {$user->id})\n";

// Get all templates
echo "\n2. Getting templates...\n";
$templates = InvitationTemplate::take(2)->get();
if ($templates->count() < 2) {
    echo "   ✗ ERROR: Need at least 2 templates. Run: php artisan db:seed --class=InvitationTemplateSeeder\n";
    exit(1);
}
echo "   ✓ Found {$templates->count()} templates\n";

// Create digital products for each template
echo "\n3. Creating {$templates->count()} digital products...\n";
$category = \App\Models\ProductCategory::first();
$products = [];
foreach ($templates as $template) {
    $product = Product::create([
        'category_id' => $category->id,
        'product_type' => 'digital',
        'template_id' => $template->id,
        'name' => "Digital Invitation - {$template->name}",
        'description' => "Test digital invitation - {$template->name}",
        'base_price' => 150000,
        'slug' => 'test-multi-' . $template->id . '-' . time(),
        'weight' => 0,
        'min_order_quantity' => 1,
        'is_active' => true,
    ]);
    $products[] = $product;
    echo "   ✓ Created product: {$product->name}\n";
}

// Create order
echo "\n4. Creating order with {$templates->count()} items...\n";
$totalAmount = count($products) * 150000;
$order = Order::create([
    'customer_id' => $user->id,
    'order_number' => 'TEST-MULTI-' . time(),
    'total_amount' => $totalAmount,
    'order_status' => 'Paid',
    'shipping_address' => 'Test Address',
    'shipping_cost' => 0,
]);
echo "   ✓ Created order: {$order->order_number} (ID: {$order->id})\n";

// Create order items
echo "\n5. Creating {$templates->count()} order items...\n";
foreach ($products as $product) {
    OrderItem::create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'quantity' => 1,
        'price' => 150000,
    ]);
    echo "   ✓ Created order item for: {$product->name}\n";
}

// Create payment
echo "\n6. Creating payment...\n";
$payment = Payment::create([
    'order_id' => $order->id,
    'transaction_id' => 'TEST-MULTI-TRX-' . time(),
    'payment_type' => 'full',
    'status' => 'Paid',
    'amount' => $totalAmount,
    'payment_method' => 'credit_card',
]);
echo "   ✓ Created payment: {$payment->transaction_id}\n";

// Create invitation details
echo "\n7. Creating invitation details...\n";
$invitationDetails = InvitationDetail::create([
    'order_id' => $order->id,
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
    'reception_time' => '18:00:00',
    'reception_location' => 'Grand Hyatt Hotel, Bandung',
    'gmaps_link' => 'https://maps.google.com/?q=Grand+Hyatt+Bandung',
]);
echo "   ✓ Created invitation details\n";

// Count before
echo "\n8. Checking state before job...\n";
$invitationsBefore = DigitalInvitation::where('order_id', $order->id)->count();
$notificationsBefore = Notification::where('user_id', $user->id)
    ->where('type', 'digital_invitation_ready')
    ->count();
echo "   - Existing invitations for this order: {$invitationsBefore}\n";
echo "   - Existing notifications: {$notificationsBefore}\n";

// Dispatch job
echo "\n9. Dispatching job...\n";
$startTime = microtime(true);
ProcessDigitalInvitations::dispatch($order);
$dispatchTime = (microtime(true) - $startTime) * 1000;
echo "   ✓ Job dispatched in " . number_format($dispatchTime, 2) . "ms\n";
echo "   → This should be < 100ms (asynchronous)\n";

// Wait for processing
echo "\n10. Waiting for job to complete...\n";
echo "   ⏳ Waiting 10 seconds for queue worker...\n";
sleep(10);

// Check results
echo "\n11. Checking results...\n";
$invitationsAfter = DigitalInvitation::where('order_id', $order->id)->count();
$notificationsAfter = Notification::where('user_id', $user->id)
    ->where('type', 'digital_invitation_ready')
    ->where('created_at', '>', now()->subMinutes(1))
    ->count();

$expectedCount = count($products);
echo "   - Digital invitations created: {$invitationsAfter} (expected: {$expectedCount})\n";
echo "   - Notifications created: {$notificationsAfter} (expected: {$expectedCount})\n";

if ($invitationsAfter === $expectedCount) {
    echo "\n✅ SUCCESS! All {$expectedCount} invitations created:\n";
    $invitations = DigitalInvitation::where('order_id', $order->id)->with('template')->get();
    foreach ($invitations as $inv) {
        echo "   - {$inv->template->name}: {$inv->slug} (status: {$inv->status})\n";
    }
} else {
    echo "\n✗ FAILED: Expected {$expectedCount} invitations but got {$invitationsAfter}\n";
    echo "   Check logs: tail -f storage/logs/laravel.log\n";
    exit(1);
}

if ($notificationsAfter === $expectedCount) {
    echo "\n✅ SUCCESS! All {$expectedCount} notifications created\n";
} else {
    echo "\n⚠️  WARNING: Expected {$expectedCount} notifications but got {$notificationsAfter}\n";
}

// Performance check
echo "\n12. Performance metrics...\n";
echo "   - Webhook dispatch time: " . number_format($dispatchTime, 2) . "ms\n";
if ($dispatchTime < 100) {
    echo "   ✅ PASS: Dispatch time < 100ms (no webhook timeout)\n";
} else {
    echo "   ⚠️  SLOW: Dispatch time > 100ms\n";
}

// Summary
echo "\n========================================\n";
echo "Test Summary\n";
echo "========================================\n";
echo "Order ID: {$order->id}\n";
echo "Products: {$expectedCount}\n";
echo "Invitations: {$invitationsAfter}/{$expectedCount} created\n";
echo "Notifications: {$notificationsAfter}/{$expectedCount} created\n";
echo "Dispatch time: " . number_format($dispatchTime, 2) . "ms\n";

if ($invitationsAfter === $expectedCount && $notificationsAfter === $expectedCount) {
    echo "\n✅ All tests passed! Multiple products handled correctly.\n\n";
} else {
    echo "\n⚠️  Some tests failed. Check logs for details.\n\n";
}
