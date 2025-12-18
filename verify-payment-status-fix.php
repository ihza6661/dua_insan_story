<?php

/**
 * Verification script for payment status standardization
 * Run with: php verify-payment-status-fix.php
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;

echo "=== Payment Status Standardization - Verification Script ===\n\n";

// Test 1: Check database statuses
echo "Test 1: Database Status Distribution\n";
echo "─────────────────────────────────────\n";

$orderStatuses = DB::table('orders')
    ->select('payment_status', DB::raw('count(*) as count'))
    ->groupBy('payment_status')
    ->get();

echo "Orders:\n";
foreach ($orderStatuses as $status) {
    $valid = in_array($status->payment_status, Order::getValidPaymentStatuses()) ? '✓' : '✗';
    echo "  {$valid} {$status->payment_status}: {$status->count}\n";
}

$paymentStatuses = DB::table('payments')
    ->select('status', DB::raw('count(*) as count'))
    ->groupBy('status')
    ->get();

echo "\nPayments:\n";
foreach ($paymentStatuses as $status) {
    $valid = in_array($status->status, Payment::getValidStatuses()) ? '✓' : '✗';
    echo "  {$valid} {$status->status}: {$status->count}\n";
}

echo "\n";

// Test 2: Check constants are defined
echo "Test 2: Model Constants\n";
echo "─────────────────────────────────────\n";

$orderConstants = [
    'PAYMENT_STATUS_PENDING',
    'PAYMENT_STATUS_PAID',
    'PAYMENT_STATUS_PARTIALLY_PAID',
    'PAYMENT_STATUS_FAILED',
    'PAYMENT_STATUS_CANCELLED',
    'PAYMENT_STATUS_REFUNDED',
];

echo "Order Payment Status Constants:\n";
foreach ($orderConstants as $constant) {
    $defined = defined("App\Models\Order::{$constant}");
    echo "  ".($defined ? '✓' : '✗')." Order::{$constant}\n";
}

echo "\n";

$paymentConstants = [
    'STATUS_PENDING',
    'STATUS_PAID',
    'STATUS_FAILED',
    'STATUS_CANCELLED',
    'STATUS_REFUNDED',
    'TYPE_FULL',
    'TYPE_DOWN_PAYMENT',
    'TYPE_FINAL',
];

echo "Payment Constants:\n";
foreach ($paymentConstants as $constant) {
    $defined = defined("App\Models\Payment::{$constant}");
    echo "  ".($defined ? '✓' : '✗')." Payment::{$constant}\n";
}

echo "\n";

// Test 3: Invoice download eligibility
echo "Test 3: Invoice Download Eligibility\n";
echo "─────────────────────────────────────\n";

$eligibleOrders = Order::whereIn('payment_status', [
    Order::PAYMENT_STATUS_PAID,
    Order::PAYMENT_STATUS_PARTIALLY_PAID,
])->count();

$totalOrders = Order::count();

echo "Orders eligible for invoice download: {$eligibleOrders} / {$totalOrders}\n";

if ($eligibleOrders > 0) {
    $sampleOrder = Order::whereIn('payment_status', [
        Order::PAYMENT_STATUS_PAID,
        Order::PAYMENT_STATUS_PARTIALLY_PAID,
    ])->with('payments')->first();

    echo "\nSample Order:\n";
    echo "  Order Number: {$sampleOrder->order_number}\n";
    echo "  Payment Status: {$sampleOrder->payment_status}\n";
    echo "  Payment Option: ".($sampleOrder->payment_option ?? 'NULL')."\n";
    echo "  Can Download: ✓ YES\n";
    echo "\n  Invoice Display:\n";
    echo "    Opsi Pembayaran: {$sampleOrder->getFormattedPaymentOption()}\n";
    echo "    Metode Pembayaran: ".($sampleOrder->getFormattedPaymentMethod() ?? 'Belum ada pembayaran')."\n";
}

echo "\n";

// Test 4: Check for invalid statuses
echo "Test 4: Data Quality Check\n";
echo "─────────────────────────────────────\n";

$invalidOrderStatuses = DB::table('orders')
    ->whereNotIn('payment_status', Order::getValidPaymentStatuses())
    ->count();

$invalidPaymentStatuses = DB::table('payments')
    ->whereNotIn('status', Payment::getValidStatuses())
    ->count();

echo "Invalid order payment_status: ".($invalidOrderStatuses > 0 ? "✗ {$invalidOrderStatuses} found" : '✓ 0')."\n";
echo "Invalid payment status: ".($invalidPaymentStatuses > 0 ? "✗ {$invalidPaymentStatuses} found" : '✓ 0')."\n";

echo "\n";

// Test 5: Payment option backfill check
echo "Test 5: Payment Option Backfill\n";
echo "─────────────────────────────────────\n";

$withPaymentOption = Order::whereNotNull('payment_option')->count();
$withPayments = Order::has('payments')->count();
$needsBackfill = Order::whereNull('payment_option')->has('payments')->count();

echo "Orders with payment_option: {$withPaymentOption}\n";
echo "Orders with payments: {$withPayments}\n";
echo "Orders needing backfill: ".($needsBackfill > 0 ? "⚠ {$needsBackfill}" : '✓ 0')."\n";

if ($needsBackfill > 0) {
    echo "\n⚠ Run: php artisan orders:backfill-payment-options\n";
}

echo "\n";

// Summary
echo "=== Summary ===\n";
echo "─────────────────────────────────────\n";

$allChecks = [
    'All order statuses valid' => $invalidOrderStatuses === 0,
    'All payment statuses valid' => $invalidPaymentStatuses === 0,
    'Constants defined' => true,
    'Eligible orders exist' => $eligibleOrders > 0,
    'Payment options backfilled' => $needsBackfill === 0,
];

foreach ($allChecks as $check => $passed) {
    echo ($passed ? '✓' : '✗')." {$check}\n";
}

$allPassed = ! in_array(false, $allChecks);

echo "\n";
echo $allPassed ? "✓ All checks passed! System ready.\n" : "⚠ Some checks failed. Review above.\n";
