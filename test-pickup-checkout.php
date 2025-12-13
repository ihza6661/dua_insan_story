<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Models\Product;
use App\Models\Cart;
use Illuminate\Support\Facades\Http;

try {
    echo "Testing Pickup Checkout\n";
    echo "======================\n\n";
    
    // Get or create test user
    $user = User::where('email', 'test@example.com')->first();
    if (!$user) {
        echo "Creating test user...\n";
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'full_name' => 'Test User',
            'phone_number' => '08123456789',
            'address' => 'Test Address 123',
            'postal_code' => '12345',
            'city_id' => 1,
            'city_name' => 'Test City',
            'province_id' => 1,
            'province_name' => 'Test Province',
        ]);
    }
    
    echo "User ID: {$user->id}\n";
    echo "User Email: {$user->email}\n\n";
    
    // Get a physical product
    $product = Product::where('product_type', 'physical')->first();
    if (!$product) {
        echo "ERROR: No physical product found\n";
        exit(1);
    }
    
    echo "Product: {$product->name}\n";
    echo "Product Type: {$product->product_type}\n\n";
    
    // Create or get cart
    $cart = Cart::firstOrCreate(['customer_id' => $user->id]);
    
    // Add product to cart if not already there
    $cartItem = $cart->items()->where('product_id', $product->id)->first();
    if (!$cartItem) {
        echo "Adding product to cart...\n";
        $cart->items()->create([
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => $product->base_price,
            'variant_id' => null,
        ]);
    }
    
    $cart->load('items');
    echo "Cart items: {$cart->items->count()}\n\n";
    
    // Create a Sanctum token for the user
    $token = $user->createToken('test-token')->plainTextToken;
    
    // Prepare checkout payload with PICKUP method
    $payload = [
        'bride_full_name' => 'Test Bride',
        'groom_full_name' => 'Test Groom',
        'bride_nickname' => 'Bride',
        'groom_nickname' => 'Groom',
        'bride_parents' => 'Test Bride Parents',
        'groom_parents' => 'Test Groom Parents',
        'akad_date' => '2025-12-25',
        'akad_time' => '10:00 WIB',
        'akad_location' => 'Test Mosque',
        'reception_date' => '2025-12-25',
        'reception_time' => '18:00 WIB',
        'reception_location' => 'Test Hall',
        'shipping_address' => $user->address,
        'postal_code' => $user->postal_code,
        'shipping_method' => 'pickup',  // This is the key - PICKUP method
        'shipping_cost' => '0',  // Should be 0 for pickup
        'payment_option' => 'full',
    ];
    
    echo "Testing Checkout with PICKUP method...\n";
    echo "Payload:\n";
    print_r($payload);
    echo "\n";
    
    // Make API request
    $response = Http::withToken($token)
        ->post('http://127.0.0.1:8000/api/v1/checkout', $payload);
    
    echo "Response Status: {$response->status()}\n";
    echo "Response Body:\n";
    echo json_encode($response->json(), JSON_PRETTY_PRINT) . "\n\n";
    
    if ($response->successful()) {
        echo "✅ SUCCESS: Checkout with pickup method worked!\n";
        $orderData = $response->json('data');
        echo "Order ID: {$orderData['id']}\n";
        echo "Order Number: {$orderData['order_number']}\n";
    } else {
        echo "❌ FAILED: Checkout returned error\n";
        echo "Error Message: " . $response->json('message') . "\n";
    }
    
} catch (\Exception $e) {
    echo "❌ EXCEPTION: {$e->getMessage()}\n";
    echo "File: {$e->getFile()}:{$e->getLine()}\n";
    echo "Trace:\n{$e->getTraceAsString()}\n";
}
