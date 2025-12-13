<?php

require __DIR__.'/vendor/autoload.php';

use Illuminate\Http\UploadedFile;

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
    'bride_full_name' => 'Sarah Jessica Johnson',
    'bride_nickname' => 'Sarah',
    'bride_parents' => 'Robert Johnson & Mary Johnson',
    'groom_full_name' => 'Michael David Smith',
    'groom_nickname' => 'Michael',
    'groom_parents' => 'John Smith & Patricia Smith',
    'akad_date' => '2025-03-15',
    'akad_time' => '09:00:00',
    'akad_location' => 'Grand Ballroom Hotel Mulia',
    'reception_date' => '2025-03-15',
    'reception_time' => '18:00:00',
    'reception_location' => 'Grand Ballroom Hotel Mulia',
    'gmaps_link' => 'https://google.com/maps?q=Grand+Ballroom+Hotel+Mulia',
    
    // Shipping (not required for digital only)
    'shipping_address' => null,
    'postal_code' => null,
    'shipping_method' => null,
    'shipping_cost' => 0,
    
    // Payment
    'payment_option' => 'full',
    'is_digital_only' => true,
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
        imagedestroy($image);
    }
    
    // Prepare multipart form data
    $multipart = [
        [
            'name' => 'items',
            'contents' => json_encode($checkoutData['items']),
        ],
        [
            'name' => 'contact',
            'contents' => json_encode($checkoutData['contact']),
        ],
        [
            'name' => 'delivery_option',
            'contents' => $checkoutData['delivery_option'],
        ],
        [
            'name' => 'prewedding_photo',
            'contents' => fopen($preweddingPhotoPath, 'r'),
            'filename' => 'prewedding.jpg',
        ],
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
            
            if (isset($orderData['payment_url'])) {
                echo "  Payment URL: {$orderData['payment_url']}\n";
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
                $notifications = $user->notifications()->latest()->take(3)->get();
                echo "\n🔔 Notifications Created: " . $notifications->count() . "\n";
                foreach ($notifications as $notification) {
                    echo "  - Type: {$notification->type}\n";
                    echo "    Message: {$notification->data['message']}\n";
                    echo "    Read: " . ($notification->read_at ? 'Yes' : 'No') . "\n";
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
    echo "Trace: " . $e->getTraceAsString() . "\n";
}

echo "\n✅ Test completed!\n";
