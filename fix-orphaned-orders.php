<?php

/**
 * Fix Orphaned Orders Script
 * 
 * This script creates Payment records for orders that are marked as 'paid'
 * but have no corresponding payment records in the payments table.
 * 
 * This is needed for the invoice to display payment information correctly.
 * 
 * Usage: php fix-orphaned-orders.php
 */

require __DIR__ . '/vendor/autoload.php';

use Illuminate\Support\Facades\DB;
use App\Models\Order;
use App\Models\Payment;

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== Fixing Orphaned Orders ===\n\n";

// Find orders marked as paid but with no payment records
$orphanedOrders = Order::where('payment_status', Order::PAYMENT_STATUS_PAID)
    ->whereDoesntHave('payments')
    ->get();

if ($orphanedOrders->isEmpty()) {
    echo "✅ No orphaned orders found. All paid orders have payment records.\n";
    exit(0);
}

echo "Found {$orphanedOrders->count()} orphaned order(s):\n";
foreach ($orphanedOrders as $order) {
    echo "  - {$order->order_number} (Total: Rp " . number_format($order->total_amount, 0, ',', '.') . ")\n";
}

echo "\n";
echo "This script will create Payment records for these orders.\n";
echo "Do you want to continue? (yes/no): ";

$handle = fopen("php://stdin", "r");
$line = fgets($handle);
$answer = trim(strtolower($line));
fclose($handle);

if ($answer !== 'yes' && $answer !== 'y') {
    echo "Aborted.\n";
    exit(0);
}

echo "\n=== Creating Payment Records ===\n\n";

DB::beginTransaction();

try {
    $created = 0;
    
    foreach ($orphanedOrders as $order) {
        echo "Processing order: {$order->order_number}\n";
        
        // Create payment record
        $payment = Payment::create([
            'order_id' => $order->id,
            'transaction_id' => 'BACKFILL-' . $order->order_number . '-' . time(),
            'payment_gateway' => 'midtrans',
            'amount' => $order->total_amount,
            'status' => Payment::STATUS_PAID,
            'payment_type' => Payment::TYPE_FULL,
            'paid_at' => $order->updated_at, // Use order's updated_at as approximation
            'raw_response' => json_encode([
                'note' => 'Backfilled payment record for orphaned order',
                'created_by' => 'fix-orphaned-orders.php',
                'created_at' => now()->toISOString(),
            ]),
        ]);
        
        echo "  ✅ Created Payment ID {$payment->id} (transaction_id: {$payment->transaction_id})\n";
        echo "     Amount: Rp " . number_format($payment->amount, 0, ',', '.') . "\n";
        echo "     Type: {$payment->payment_type}\n";
        echo "     Status: {$payment->status}\n\n";
        
        $created++;
    }
    
    DB::commit();
    
    echo "\n=== Summary ===\n";
    echo "✅ Successfully created {$created} payment record(s)\n";
    echo "\n";
    echo "Next steps:\n";
    echo "1. Run: php artisan orders:backfill-payment-options\n";
    echo "2. Verify invoice displays correctly\n";
    echo "3. Run: php verify-payment-status-fix.php\n";
    
} catch (\Exception $e) {
    DB::rollBack();
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
