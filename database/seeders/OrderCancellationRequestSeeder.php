<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\OrderCancellationRequest;
use App\Models\User;
use Illuminate\Database\Seeder;

class OrderCancellationRequestSeeder extends Seeder
{
    private array $cancellationReasons = [
        // Indonesian reasons
        'Salah pilih desain undangan, ingin ganti dengan yang lain',
        'Pernikahan ditunda karena kondisi keluarga',
        'Ingin mengubah warna dan tema undangan',
        'Biaya tidak sesuai budget, ingin cari yang lebih murah',
        'Sudah menemukan vendor lain dengan harga lebih baik',
        'Desain tidak sesuai ekspektasi setelah lihat preview',
        'Kesalahan input data, perlu pesan ulang',
        'Perubahan lokasi acara pernikahan',
        'Jumlah undangan yang dipesan terlalu banyak',
        'Ingin menambah fitur tambahan yang tidak tersedia',
        
        // Generic/English
        'Customer requested cancellation',
        'Duplicate order by mistake',
        'Payment issues, need to reorder',
        'Changed mind about design',
        'Found better alternative',
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🔄 Creating order cancellation requests...');

        // Find customer and admin users
        $customer = User::where('role', 'customer')->first();
        $admin = User::where('role', 'admin')->first();

        if (!$customer) {
            $this->command->error('No customer user found!');
            return;
        }

        if (!$admin) {
            $this->command->error('No admin user found!');
            return;
        }

        $this->command->info("Found customer: {$customer->email}");
        $this->command->info("Found admin: {$admin->email}");

        // ===== 1. APPROVED REQUESTS (linked to cancelled orders) =====
        $this->command->info("\n✅ Creating APPROVED cancellation requests for cancelled orders...");
        
        $cancelledOrders = Order::where('order_status', Order::STATUS_CANCELLED)
            ->where('customer_id', $customer->id)
            ->get();

        if ($cancelledOrders->isEmpty()) {
            $this->command->warn('No cancelled orders found. Run OrderSeeder first!');
        } else {
            foreach ($cancelledOrders as $order) {
                $reason = $this->cancellationReasons[array_rand($this->cancellationReasons)];
                
                // Determine refund status mix
                $rand = rand(1, 100);
                if ($rand <= 70) {
                    // 70% refunds completed
                    $refundStatus = OrderCancellationRequest::REFUND_STATUS_COMPLETED;
                } elseif ($rand <= 85) {
                    // 15% refunds processing
                    $refundStatus = OrderCancellationRequest::REFUND_STATUS_PROCESSING;
                } elseif ($rand <= 95) {
                    // 10% refunds pending
                    $refundStatus = OrderCancellationRequest::REFUND_STATUS_PENDING;
                } else {
                    // 5% refunds failed
                    $refundStatus = OrderCancellationRequest::REFUND_STATUS_FAILED;
                }

                OrderCancellationRequest::create([
                    'order_id' => $order->id,
                    'requested_by' => $customer->id,
                    'cancellation_reason' => $reason,
                    'status' => OrderCancellationRequest::STATUS_APPROVED,
                    'reviewed_by' => $admin->id,
                    'reviewed_at' => $order->created_at->copy()->addHours(rand(1, 6)),
                    'admin_notes' => 'Permintaan pembatalan disetujui. Refund akan diproses.',
                    'refund_initiated' => true,
                    'refund_amount' => $order->total_amount,
                    'refund_transaction_id' => 'REFUND-' . $order->order_number,
                    'refund_status' => $refundStatus,
                    'stock_restored' => ($refundStatus === OrderCancellationRequest::REFUND_STATUS_COMPLETED),
                ]);

                $refundEmoji = match($refundStatus) {
                    'completed' => '💰',
                    'processing' => '⏳',
                    'pending' => '⏸️',
                    'failed' => '❌',
                    default => '❓'
                };

                $this->command->info("  ✅ {$order->order_number} - Approved - Refund: {$refundEmoji} {$refundStatus}");
            }
        }

        // ===== 2. PENDING REQUESTS (awaiting admin review) =====
        $this->command->info("\n⏳ Creating PENDING cancellation requests...");

        $pendingOrders = Order::whereIn('order_status', [
            Order::STATUS_PAID,
            Order::STATUS_PROCESSING,
            Order::STATUS_DESIGN_APPROVAL,
        ])
        ->where('customer_id', $customer->id)
        ->take(3)
        ->get();

        foreach ($pendingOrders as $order) {
            $reason = $this->cancellationReasons[array_rand($this->cancellationReasons)];
            
            $request = OrderCancellationRequest::create([
                'order_id' => $order->id,
                'requested_by' => $customer->id,
                'cancellation_reason' => $reason,
                'status' => OrderCancellationRequest::STATUS_PENDING,
                'reviewed_by' => null,
                'reviewed_at' => null,
                'admin_notes' => null,
                'refund_initiated' => false,
                'refund_amount' => null,
                'refund_transaction_id' => null,
                'refund_status' => null,
                'stock_restored' => false,
            ]);

            $this->command->info("  ⏳ {$order->order_number} - Pending - {$order->order_status}");
        }

        // ===== 3. REJECTED REQUESTS (orders stayed active) =====
        $this->command->info("\n❌ Creating REJECTED cancellation requests...");

        $activeOrders = Order::whereIn('order_status', [
            Order::STATUS_IN_PRODUCTION,
            Order::STATUS_SHIPPED,
            Order::STATUS_COMPLETED,
        ])
        ->where('customer_id', $customer->id)
        ->take(2)
        ->get();

        $rejectReasons = [
            'Pesanan sudah dalam tahap produksi, tidak bisa dibatalkan',
            'Undangan sudah dikirim, pembatalan tidak memungkinkan',
            'Produksi hampir selesai, mohon maaf tidak bisa dibatalkan',
            'Refund tidak memungkinkan karena custom order',
        ];

        foreach ($activeOrders as $order) {
            $reason = $this->cancellationReasons[array_rand($this->cancellationReasons)];
            $rejectReason = $rejectReasons[array_rand($rejectReasons)];
            
            $request = OrderCancellationRequest::create([
                'order_id' => $order->id,
                'requested_by' => $customer->id,
                'cancellation_reason' => $reason,
                'status' => OrderCancellationRequest::STATUS_REJECTED,
                'reviewed_by' => $admin->id,
                'reviewed_at' => now()->subDays(rand(1, 5)),
                'admin_notes' => $rejectReason,
                'refund_initiated' => false,
                'refund_amount' => null,
                'refund_transaction_id' => null,
                'refund_status' => null,
                'stock_restored' => false,
            ]);

            $this->command->info("  ❌ {$order->order_number} - Rejected - {$order->order_status}");
        }

        // ===== SUMMARY =====
        $total = OrderCancellationRequest::count();
        $pending = OrderCancellationRequest::where('status', OrderCancellationRequest::STATUS_PENDING)->count();
        $approved = OrderCancellationRequest::where('status', OrderCancellationRequest::STATUS_APPROVED)->count();
        $rejected = OrderCancellationRequest::where('status', OrderCancellationRequest::STATUS_REJECTED)->count();

        $this->command->info("\n✅ Cancellation requests seeding completed!");
        $this->command->info("Total requests: {$total}");
        $this->command->info("  - Pending: {$pending}");
        $this->command->info("  - Approved: {$approved}");
        $this->command->info("  - Rejected: {$rejected}");
    }
}
