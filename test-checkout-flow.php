<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$baseUrl = 'http://127.0.0.1:8000';
$token = '4|dtyqkrRlyIYnfdyNMslZb0MZqPH6VEusRt2xuNiB4317e724';

echo "\n🧪 Testing Complete Checkout Flow\n";
echo "==================================\n\n";

// Get digital product details
$digitalProduct = App\Models\Product::where('product_type', 'digital')->first();
echo "📦 Product: {$digitalProduct->name} (ID: {$digitalProduct->id})\n";
echo "💰 Price: Rp " . number_format($digitalProduct->price, 0, ',', '.') . "\n\n";

// Prepare checkout data (flat structure as per StoreRequest)
$checkoutData = [
    // Wedding data
    'bride_full_name' => 'Siti Nurhaliza binti Ahmad',
    'bride_nickname' => 'Siti',
    'bride_parents' => 'Ahmad bin Hassan & Fatimah binti Ibrahim',
    'groom_full_name' => 'Muhammad Rizki bin Abdullah',
    'groom_nickname' => 'Rizki',
    'groom_parents' => 'Abdullah bin Yusuf & Aisyah binti Omar',
    'akad_date' => '2025-06-20',
    'akad_time' => '08:00:00',
    'akad_location' => 'Masjid Al-Ikhlas, Jakarta Selatan',
    'reception_date' => '2025-06-20',
    'reception_time' => '19:00:00',
    'reception_location' => 'Ballroom Hotel Grand Indonesia',
    'gmaps_link' => 'https://google.com/maps?q=Hotel+Grand+Indonesia+Jakarta',
    
    // Shipping (nullable for digital only)
    'shipping_address' => 'Digital Product - No Shipping Required',
    'shipping_method' => 'none',
    'is_digital_only' => true,
    'shipping_cost' => 0,
    
    // Payment
    'payment_option' => 'full',
];

echo "📋 Checkout Data:\n";
echo json_encode($checkoutData, JSON_PRETTY_PRINT) . "\n\n";

// Initialize HTTP client
$client = new \GuzzleHttp\Client([
    'base_uri' => $baseUrl,
    'timeout' => 30,
    'http_errors' => false,
]);

echo "🚀 Sending checkout request...\n";
$startTime = microtime(true);

try {
    // Check if prewedding photo exists
    $preweddingPhotoPath = storage_path('app/test-prewedding.jpg');
    if (!file_exists($preweddingPhotoPath)) {
        echo "⚠️  No prewedding photo found, creating one...\n";
        $image = imagecreatetruecolor(800, 600);
        $bgColor = imagecolorallocate($image, 200, 220, 240);
        $textColor = imagecolorallocate($image, 50, 50, 50);
        imagefill($image, 0, 0, $bgColor);
        imagestring($image, 5, 250, 280, 'Test Prewedding Photo', $textColor);
        imagejpeg($image, $preweddingPhotoPath, 90);
    }
    
    // Prepare multipart form data
    $multipart = [];
    
    // Add all checkout fields
    foreach ($checkoutData as $key => $value) {
        if ($value !== null) {
            $multipart[] = [
                'name' => $key,
                'contents' => is_bool($value) ? ($value ? '1' : '0') : (string)$value,
            ];
        }
    }
    
    // Add prewedding photo
    $multipart[] = [
        'name' => 'prewedding_photo',
        'contents' => fopen($preweddingPhotoPath, 'r'),
        'filename' => 'prewedding.jpg',
    ];

    $response = $client->post('/api/v1/checkout', [
        'headers' => [
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
        ],
        'multipart' => $multipart,
    ]);

    $endTime = microtime(true);
    $duration = round(($endTime - $startTime) * 1000, 2);

    $statusCode = $response->getStatusCode();
    $body = json_decode($response->getBody()->getContents(), true);

    echo "\n📊 Response:\n";
    echo "Status: {$statusCode}\n";
    echo "Duration: {$duration}ms\n";
    echo "Body: " . json_encode($body, JSON_PRETTY_PRINT) . "\n\n";

    if ($statusCode === 200 || $statusCode === 201) {
        echo "✅ Checkout successful!\n\n";
        
        if (isset($body['data'])) {
            $orderData = $body['data'];
            echo "📝 Order Details:\n";
            echo "  Order Number: {$orderData['order_number']}\n";
            echo "  Status: {$orderData['status']}\n";
            echo "  Total: Rp " . number_format($orderData['total_amount'], 0, ',', '.') . "\n";
            
            if (isset($body['snap_token'])) {
                echo "  Snap Token: {$body['snap_token']}\n";
            }
            
            echo "\n";
            
            // Wait for queue processing
            echo "⏳ Waiting for queue to process (10 seconds)...\n";
            sleep(10);
            
            // Check if digital invitation was created
            $order = App\Models\Order::where('order_number', $orderData['order_number'])->first();
            if ($order) {
                $invitations = $order->digitalInvitations;
                echo "\n📨 Digital Invitations Created: " . $invitations->count() . "\n";
                
                foreach ($invitations as $invitation) {
                    echo "  - ID: {$invitation->id}\n";
                    echo "    Slug: {$invitation->slug}\n";
                    echo "    Status: {$invitation->status}\n";
                    echo "    URL: " . url("/invitation/{$invitation->slug}") . "\n";
                }
                
                // Check notifications
                $user = App\Models\User::find($order->user_id);
                if ($user) {
                    $notifications = $user->notifications()->latest()->take(3)->get();
                    echo "\n🔔 Notifications Created: " . $notifications->count() . "\n";
                    foreach ($notifications as $notification) {
                        $data = json_decode($notification->data, true);
                        echo "  - Type: {$notification->type}\n";
                        echo "    Message: " . ($data['message'] ?? 'N/A') . "\n";
                        echo "    Read: " . ($notification->read_at ? 'Yes' : 'No') . "\n";
                    }
                }
            }
        }
    } else {
        echo "❌ Checkout failed!\n";
        if (isset($body['message'])) {
            echo "Error: {$body['message']}\n";
        }
        if (isset($body['errors'])) {
            echo "Validation Errors:\n";
            foreach ($body['errors'] as $field => $errors) {
                echo "  - {$field}: " . implode(', ', $errors) . "\n";
            }
        }
    }

} catch (Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}

echo "\n✅ Test completed!\n";
