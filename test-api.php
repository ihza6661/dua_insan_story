#!/usr/bin/env php
<?php

/**
 * Manual API Testing Script for Digital Invitation Endpoints
 * Usage: php test-api.php
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Http;

$baseUrl = 'http://localhost:8000/api/v1';
$testResults = [];

function testEndpoint($name, $method, $url, $data = [], $headers = [])
{
    global $baseUrl, $testResults;
    
    echo "\n🧪 Testing: {$name}\n";
    echo "   {$method} {$url}\n";
    
    try {
        $response = Http::withHeaders($headers)->$method($baseUrl.$url, $data);
        
        $status = $response->status();
        $body = $response->json();
        
        if ($status >= 200 && $status < 300) {
            echo "   ✅ Success ({$status})\n";
            $testResults[$name] = 'PASS';
        } else {
            echo "   ❌ Failed ({$status})\n";
            $testResults[$name] = 'FAIL';
        }
        
        if (isset($body['message'])) {
            echo "   📝 {$body['message']}\n";
        }
        
        if (isset($body['data']) && is_array($body['data'])) {
            $count = count($body['data']);
            echo "   📊 Data count: {$count}\n";
        }
        
        return $response;
    } catch (Exception $e) {
        echo "   ❌ Error: {$e->getMessage()}\n";
        $testResults[$name] = 'ERROR';
        return null;
    }
}

echo "\n";
echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║  Digital Invitation API Manual Testing                    ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n";

// Test 1: List Templates (Public)
testEndpoint(
    'List Templates (Public)',
    'get',
    '/customer/invitation-templates'
);

// Test 2: Get Template by Slug (Public)
testEndpoint(
    'Get Template Detail (Public)',
    'get',
    '/customer/invitation-templates/sakeenah-islamic-modern'
);

// Test 3: Create Test User
echo "\n📝 Creating test user...\n";
$user = \App\Models\User::firstOrCreate(
    ['email' => 'test-digital@example.com'],
    [
        'full_name' => 'Digital Test User',
        'password' => bcrypt('password123'),
        'role' => 'customer',
        'phone_number' => '081234567890',
    ]
);
echo "   ✅ User created/found: {$user->email}\n";

// Test 4: Login to get token
echo "\n🔐 Getting auth token...\n";
$loginResponse = Http::post($baseUrl.'/login', [
    'email' => 'test-digital@example.com',
    'password' => 'password123',
]);

if ($loginResponse->successful()) {
    $token = $loginResponse->json('data.token');
    echo "   ✅ Token obtained\n";
    
    $authHeaders = [
        'Authorization' => "Bearer {$token}",
        'Accept' => 'application/json',
    ];
    
    // Test 5: List User's Invitations (Auth Required)
    testEndpoint(
        'List User Invitations (Auth)',
        'get',
        '/digital-invitations',
        [],
        $authHeaders
    );
    
    // Create a test invitation for further testing
    echo "\n📝 Creating test invitation...\n";
    $category = \App\Models\ProductCategory::firstOrCreate(
        ['name' => 'Digital Invitations'],
        ['slug' => 'digital-invitations', 'description' => 'Digital invitation templates']
    );
    
    $template = \App\Models\InvitationTemplate::first();
    
    $product = \App\Models\Product::firstOrCreate(
        ['name' => 'Digital Invitation - Sakeenah'],
        [
            'slug' => 'digital-invitation-sakeenah',
            'category_id' => $category->id,
            'product_type' => 'digital',
            'template_id' => $template->id,
            'base_price' => 150000,
            'description' => 'Digital invitation template',
            'weight' => 0,
            'is_active' => true,
        ]
    );
    
    $order = \App\Models\Order::create([
        'customer_id' => $user->id,
        'order_number' => 'TEST-'.strtoupper(substr(md5(time()), 0, 8)),
        'status' => 'Paid',
        'subtotal' => 150000,
        'total_amount' => 150000,
        'payment_method' => 'bank_transfer',
        'customer_full_name' => $user->full_name,
        'customer_email' => $user->email,
        'customer_phone' => $user->phone_number,
    ]);
    
    $service = app(\App\Services\DigitalInvitationService::class);
    $invitation = $service->createFromOrder($order);
    
    if ($invitation) {
        echo "   ✅ Test invitation created: {$invitation->slug}\n";
        
        // Test 6: Get Invitation Detail
        testEndpoint(
            'Get Invitation Detail (Auth)',
            'get',
            "/digital-invitations/{$invitation->id}",
            [],
            $authHeaders
        );
        
        // Test 7: Update Customization
        testEndpoint(
            'Update Customization (Auth)',
            'put',
            "/digital-invitations/{$invitation->id}/customize",
            [
                'bride_name' => 'Siti Aminah',
                'groom_name' => 'Ahmad Fauzi',
                'event_date' => '2025-08-15',
                'event_time' => '10:00',
                'venue_name' => 'Masjid Raya Jakarta',
                'venue_address' => 'Jl. Raya Jakarta No. 123',
            ],
            $authHeaders
        );
        
        // Test 8: Activate Invitation
        testEndpoint(
            'Activate Invitation (Auth)',
            'post',
            "/digital-invitations/{$invitation->id}/activate",
            [],
            $authHeaders
        );
        
        // Refresh invitation to get latest data
        $invitation->refresh();
        
        // Test 9: View Public Invitation (No Auth)
        testEndpoint(
            'View Public Invitation (Guest)',
            'get',
            "/invitations/{$invitation->slug}"
        );
        
    } else {
        echo "   ❌ Failed to create test invitation\n";
    }
    
} else {
    echo "   ❌ Login failed\n";
}

// Summary
echo "\n";
echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║  Test Summary                                              ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n";

$passed = count(array_filter($testResults, fn ($r) => $r === 'PASS'));
$failed = count(array_filter($testResults, fn ($r) => $r === 'FAIL'));
$errors = count(array_filter($testResults, fn ($r) => $r === 'ERROR'));

foreach ($testResults as $name => $result) {
    $icon = $result === 'PASS' ? '✅' : ($result === 'FAIL' ? '❌' : '⚠️');
    echo "{$icon} {$name}: {$result}\n";
}

echo "\n📊 Results: {$passed} passed, {$failed} failed, {$errors} errors\n";
echo "\n";
