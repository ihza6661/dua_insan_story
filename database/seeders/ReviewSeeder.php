<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Seeder;

class ReviewSeeder extends Seeder
{
    private array $reviewComments = [
        5 => [
            'Produknya sangat bagus! Kualitas cetakan jelas dan warna sesuai ekspektasi. Penjual responsif dan pengiriman cepat. Sangat puas! 😊',
            'Luar biasa! Undangan pernikahan kami sempurna. Tamu-tamu memuji desainnya. Terima kasih banyak!',
            'Kualitas premium! Bahan tebal, cetakan rapi. Packaging juga bagus. Sangat merekomendasikan! 👍',
            'Pelayanan excellent! Hasil melebihi ekspektasi. Buku tamu sangat elegan dan berkualitas tinggi.',
            'Perfect! Sesuai harapan bahkan lebih. Fast response dari admin. Highly recommended! ⭐⭐⭐⭐⭐',
        ],
        4 => [
            'Bagus, tapi pengiriman agak lama. Overall hasil cetakan memuaskan dan sesuai design yang diminta.',
            'Kualitas oke, harga bersaing. Ada sedikit perbedaan warna tapi masih acceptable. Recommended!',
            'Produk bagus, kemasan rapi. Hanya saja proses approval design agak lama. Tapi hasil akhir memuaskan.',
        ],
        3 => [
            'Cukup baik. Kualitas standar sesuai harga. Ada beberapa bagian yang kurang rapi tapi masih bisa diterima.',
            'Biasa saja. Tidak ada yang spesial tapi tidak mengecewakan juga. Sesuai ekspektasi.',
        ],
        2 => [
            'Kurang puas. Warna cetakan tidak sesuai dengan contoh yang ditampilkan. Pengiriman juga lama.',
            'Agak kecewa. Kualitas bahan tipis dan cetakan kurang tajam. Perlu perbaikan quality control.',
        ],
        1 => [
            'Sangat kecewa! Produk rusak saat sampai dan penjual tidak responsif. Mohon diperbaiki pelayanannya.',
        ],
    ];

    private array $adminResponses = [
        'Terima kasih atas review positifnya! Kami senang Anda puas dengan produk kami. Semoga pernikahan Anda lancar! 💐',
        'Terima kasih feedback-nya! Kami akan terus meningkatkan kualitas produk dan layanan kami.',
        'Terima kasih telah berbelanja di Dua Insan Story. Semoga undangan pernikahannya berkesan! 🎊',
        'Kami mohon maaf atas ketidaknyamanannya. Tim kami akan follow up pesanan Anda. Terima kasih atas kesabarannya.',
        'Terima kasih atas masukannya. Kami akan perbaiki sistem kami agar lebih baik lagi. 🙏',
    ];

    public function run(): void
    {
        $customer = User::where('email', 'customer@example.com')->first();

        if (! $customer) {
            $this->command->error('Customer user not found! Please run UserSeeder first.');

            return;
        }

        // Get all order items that can be reviewed
        $orderItems = OrderItem::whereHas('order', function ($query) use ($customer) {
            $query->where('customer_id', $customer->id)
                ->whereIn('order_status', [Order::STATUS_DELIVERED, Order::STATUS_COMPLETED]);
        })->with(['product', 'order'])->get();

        if ($orderItems->count() < 5) {
            $this->command->error('Not enough order items! Please run OrderSeeder first.');

            return;
        }

        $reviewCount = 0;
        $targetReviews = min(15, $orderItems->count());

        // Review distribution:
        // 5 stars: 6 reviews (40%)
        // 4 stars: 4 reviews (27%)
        // 3 stars: 2 reviews (13%)
        // 2 stars: 2 reviews (13%)
        // 1 star: 1 review (7%)
        $ratingDistribution = [
            5 => 6,
            4 => 4,
            3 => 2,
            2 => 2,
            1 => 1,
        ];

        foreach ($ratingDistribution as $rating => $count) {
            for ($i = 0; $i < $count && $reviewCount < $targetReviews; $i++) {
                if ($reviewCount >= $orderItems->count()) {
                    break;
                }

                $orderItem = $orderItems[$reviewCount];

                // Determine review status
                // 80% approved, 15% pending, 5% rejected
                $rand = rand(1, 100);
                if ($rand <= 80) {
                    $status = 'approved';
                } elseif ($rand <= 95) {
                    $status = 'pending';
                } else {
                    $status = 'rejected';
                }

                // Select comment based on rating
                $comments = $this->reviewComments[$rating];
                $comment = $comments[array_rand($comments)];

                // Create review
                $isApproved = ($status === 'approved');
                $review = Review::create([
                    'customer_id' => $customer->id,
                    'product_id' => $orderItem->product_id,
                    'order_item_id' => $orderItem->id,
                    'rating' => $rating,
                    'comment' => $comment,
                    'is_verified' => true,
                    'is_approved' => $isApproved,
                    'is_featured' => false, // Will set featured later
                    'helpful_count' => rand(0, 25),
                    'admin_response' => null,
                    'admin_responded_at' => null,
                ]);

                // Add admin response for some approved 4-5 star reviews
                if ($isApproved && $rating >= 4 && rand(1, 100) <= 60) {
                    $response = $this->adminResponses[array_rand($this->adminResponses)];
                    $review->update([
                        'admin_response' => $response,
                        'admin_responded_at' => now()->subDays(rand(1, 5)),
                    ]);
                }

                $reviewCount++;
                $this->command->info("Created review #{$reviewCount}: {$rating}⭐ - {$status} - Product: {$orderItem->product->name}");
            }
        }

        // Mark top 3 highest-rated approved reviews as featured
        $featuredReviews = Review::where('is_approved', true)
            ->where('rating', 5)
            ->orderBy('helpful_count', 'desc')
            ->take(3)
            ->get();

        foreach ($featuredReviews as $review) {
            $review->update(['is_featured' => true]);
            $this->command->info("⭐ Marked review #{$review->id} as featured");
        }

        $this->command->info("\n✅ Successfully created {$reviewCount} reviews!");
        $this->command->info('   - Approved: '.Review::where('is_approved', true)->count());
        $this->command->info('   - Pending/Rejected: '.Review::where('is_approved', false)->count());
        $this->command->info('   - Featured: '.Review::where('is_featured', true)->count());
    }
}
