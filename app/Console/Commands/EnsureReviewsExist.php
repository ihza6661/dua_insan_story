<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Review;
use App\Models\ReviewImage;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class EnsureReviewsExist extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reviews:ensure-exist 
                            {--force : Force re-seed reviews even if they exist}
                            {--dry-run : Show what would be created without actually creating}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Ensure reviews exist in the database. Creates them if missing.';

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

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🔍 Checking review status...');
        $this->newLine();

        $existingReviewCount = Review::count();

        // Check if reviews exist
        if ($existingReviewCount > 0 && ! $this->option('force')) {
            $this->displayExistingReviewStats($existingReviewCount);
            $this->newLine();
            $this->info('✅ Reviews already exist. Use --force to re-seed.');

            return Command::SUCCESS;
        }

        if ($this->option('force')) {
            $this->warn('⚠️  --force flag detected. This will delete existing reviews!');
            if (! $this->option('dry-run') && ! $this->confirm('Are you sure you want to continue?', false)) {
                $this->info('Aborted.');

                return Command::SUCCESS;
            }
        }

        // Find customers with completed orders
        $candidateCustomers = $this->findCandidateCustomers();

        if ($candidateCustomers->isEmpty()) {
            $this->error('❌ No customers with completed/delivered orders found!');
            $this->warn('Please run ComprehensiveOrderSeeder first to create orders.');

            return Command::FAILURE;
        }

        // Display candidates
        $this->info('📊 Found customers with reviewable orders:');
        foreach ($candidateCustomers as $candidate) {
            $this->line("   - {$candidate->email}: {$candidate->reviewable_items_count} reviewable items");
        }
        $this->newLine();

        // Select best customer (prefer customer@example.com, fallback to customer with most items)
        $selectedCustomer = $candidateCustomers->firstWhere('email', 'customer@example.com')
            ?? $candidateCustomers->first();

        $this->info("✅ Selected customer: {$selectedCustomer->email} ({$selectedCustomer->reviewable_items_count} items)");
        $this->newLine();

        if ($this->option('dry-run')) {
            $this->displayDryRunPreview($selectedCustomer);

            return Command::SUCCESS;
        }

        // Delete existing reviews if force flag
        if ($this->option('force') && $existingReviewCount > 0) {
            $this->warn('Deleting existing reviews...');
            ReviewImage::whereIn('review_id', Review::pluck('id'))->delete();
            Review::truncate();
            $this->info("✓ Deleted {$existingReviewCount} existing reviews");
            $this->newLine();
        }

        // Create reviews
        $this->info('🎨 Creating reviews...');
        $reviewsCreated = $this->createReviewsForCustomer($selectedCustomer);

        if ($reviewsCreated === 0) {
            $this->error('❌ Failed to create reviews!');

            return Command::FAILURE;
        }

        $this->newLine();
        $this->info('🖼️  Creating review images...');
        $imagesCreated = $this->createReviewImages();

        // Display summary
        $this->newLine();
        $this->displaySuccessSummary($reviewsCreated, $imagesCreated);

        return Command::SUCCESS;
    }

    /**
     * Display stats for existing reviews
     */
    private function displayExistingReviewStats(int $total): void
    {
        $approved = Review::where('is_approved', true)->count();
        $pending = Review::where('is_approved', false)->count();
        $featured = Review::where('is_featured', true)->count();
        $withImages = Review::has('images')->count();

        $this->info("📊 Current Review Statistics:");
        $this->line("   Total reviews: {$total}");
        $this->line("   ├─ Approved: {$approved}");
        $this->line("   ├─ Pending/Rejected: {$pending}");
        $this->line("   ├─ Featured: {$featured}");
        $this->line("   └─ With images: {$withImages}");
    }

    /**
     * Find customers with completed/delivered orders
     */
    private function findCandidateCustomers()
    {
        // Get customers with completed/delivered orders
        $customers = User::where('role', 'customer')
            ->whereHas('orders', function ($query) {
                $query->whereIn('order_status', [Order::STATUS_DELIVERED, Order::STATUS_COMPLETED]);
            })
            ->get();

        // Calculate reviewable items count for each customer
        foreach ($customers as $customer) {
            $customer->reviewable_items_count = OrderItem::whereHas('order', function ($query) use ($customer) {
                $query->where('customer_id', $customer->id)
                    ->whereIn('order_status', [Order::STATUS_DELIVERED, Order::STATUS_COMPLETED]);
            })->count();
        }

        // Filter customers with at least 1 reviewable item and sort
        return $customers
            ->filter(function ($customer) {
                return $customer->reviewable_items_count > 0;
            })
            ->sortByDesc('reviewable_items_count')
            ->values();
    }

    /**
     * Display dry-run preview
     */
    private function displayDryRunPreview($customer): void
    {
        $orderItems = $this->getReviewableOrderItems($customer);
        $targetReviews = min(15, $orderItems->count());

        $this->info('🔮 DRY RUN - Preview of what would be created:');
        $this->newLine();
        $this->line("Customer: {$customer->email}");
        $this->line("Reviewable items: {$orderItems->count()}");
        $this->line("Reviews to create: {$targetReviews}");
        $this->newLine();

        $ratingDistribution = $this->calculateRatingDistribution($targetReviews);
        $this->line('Rating distribution:');
        foreach ($ratingDistribution as $rating => $count) {
            $stars = str_repeat('⭐', $rating);
            $this->line("   {$stars} ({$rating} stars): {$count} reviews");
        }

        $this->newLine();
        $this->line('Approval distribution (estimated):');
        $this->line('   ✅ Approved: ~'.round($targetReviews * 0.8).' (80%)');
        $this->line('   ⏳ Pending: ~'.round($targetReviews * 0.15).' (15%)');
        $this->line('   ❌ Rejected: ~'.round($targetReviews * 0.05).' (5%)');
        $this->newLine();
        $this->line('Review images: 5 reviews will have 2-4 images each');
        $this->newLine();
        $this->info('💡 Run without --dry-run to actually create these reviews.');
    }

    /**
     * Get reviewable order items for customer
     */
    private function getReviewableOrderItems($customer)
    {
        return OrderItem::whereHas('order', function ($query) use ($customer) {
            $query->where('customer_id', $customer->id)
                ->whereIn('order_status', [Order::STATUS_DELIVERED, Order::STATUS_COMPLETED]);
        })->with(['product', 'order'])->get();
    }

    /**
     * Calculate rating distribution
     */
    private function calculateRatingDistribution(int $targetReviews): array
    {
        // Default distribution for 15 reviews
        $baseDistribution = [
            5 => 6,
            4 => 4,
            3 => 2,
            2 => 2,
            1 => 1,
        ];

        if ($targetReviews === 15) {
            return $baseDistribution;
        }

        // Scale distribution for different target counts
        $distribution = [];
        $remaining = $targetReviews;

        foreach ($baseDistribution as $rating => $count) {
            if ($remaining <= 0) {
                $distribution[$rating] = 0;
                continue;
            }

            $scaled = (int) round(($count / 15) * $targetReviews);
            $scaled = min($scaled, $remaining);
            $distribution[$rating] = $scaled;
            $remaining -= $scaled;
        }

        // Add any remaining reviews to 5-star
        if ($remaining > 0) {
            $distribution[5] += $remaining;
        }

        return $distribution;
    }

    /**
     * Create reviews for customer
     */
    private function createReviewsForCustomer($customer): int
    {
        $orderItems = $this->getReviewableOrderItems($customer);

        if ($orderItems->isEmpty()) {
            $this->error('No reviewable order items found!');

            return 0;
        }

        $targetReviews = min(15, $orderItems->count());
        $ratingDistribution = $this->calculateRatingDistribution($targetReviews);

        $reviewCount = 0;
        $bar = $this->output->createProgressBar($targetReviews);
        $bar->start();

        foreach ($ratingDistribution as $rating => $count) {
            for ($i = 0; $i < $count && $reviewCount < $targetReviews; $i++) {
                if ($reviewCount >= $orderItems->count()) {
                    break;
                }

                $orderItem = $orderItems[$reviewCount];

                // Determine review status: 80% approved, 15% pending, 5% rejected
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
                    'is_featured' => false,
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
                $bar->advance();
            }
        }

        $bar->finish();
        $this->newLine();

        // Mark top 3 highest-rated approved reviews as featured
        $featuredReviews = Review::where('is_approved', true)
            ->where('rating', 5)
            ->orderBy('helpful_count', 'desc')
            ->take(3)
            ->get();

        foreach ($featuredReviews as $review) {
            $review->update(['is_featured' => true]);
        }

        return $reviewCount;
    }

    /**
     * Create review images
     */
    private function createReviewImages(): int
    {
        $reviews = Review::where('is_approved', true)
            ->where('rating', 5)
            ->take(5)
            ->get();

        if ($reviews->count() === 0) {
            $this->warn('No approved 5-star reviews found. Skipping image seeding.');

            return 0;
        }

        $storagePath = storage_path('app/public/review-images');

        // Ensure directory exists
        if (! File::exists($storagePath)) {
            File::makeDirectory($storagePath, 0755, true);
        }

        // Create placeholder images
        $imageColors = [
            ['name' => 'invitation-sample-1.jpg', 'color' => [255, 200, 200]],
            ['name' => 'invitation-sample-2.jpg', 'color' => [200, 220, 255]],
            ['name' => 'invitation-sample-3.jpg', 'color' => [255, 240, 200]],
            ['name' => 'invitation-sample-4.jpg', 'color' => [220, 255, 220]],
            ['name' => 'guestbook-sample-1.jpg', 'color' => [255, 220, 255]],
            ['name' => 'guestbook-sample-2.jpg', 'color' => [255, 230, 200]],
            ['name' => 'product-detail-1.jpg', 'color' => [200, 240, 255]],
            ['name' => 'product-detail-2.jpg', 'color' => [255, 255, 200]],
        ];

        $createdImages = [];

        // Create actual placeholder image files using GD
        if (! function_exists('imagecreatetruecolor')) {
            $this->warn('GD extension not available. Skipping image creation.');

            return 0;
        }

        foreach ($imageColors as $imageData) {
            $filename = $imageData['name'];
            $filepath = $storagePath.'/'.$filename;

            $img = imagecreatetruecolor(500, 500);
            $color = imagecolorallocate(
                $img,
                $imageData['color'][0],
                $imageData['color'][1],
                $imageData['color'][2]
            );
            imagefill($img, 0, 0, $color);

            // Add text
            $textColor = imagecolorallocate($img, 100, 100, 100);
            $text = 'Sample Review Image';
            imagestring($img, 5, 150, 240, $text, $textColor);

            imagejpeg($img, $filepath, 90);
            imagedestroy($img);

            $createdImages[] = $filename;
        }

        // Attach images to reviews
        $totalImages = 0;
        $imageIndex = 0;

        foreach ($reviews as $review) {
            $imageCount = rand(2, 4);

            for ($i = 0; $i < $imageCount && $imageIndex < count($createdImages); $i++) {
                ReviewImage::create([
                    'review_id' => $review->id,
                    'image_path' => 'review-images/'.$createdImages[$imageIndex],
                    'alt_text' => 'Sample review image '.($i + 1),
                    'display_order' => $i + 1,
                ]);

                $imageIndex++;
                $totalImages++;

                // Reset index if we run out of images
                if ($imageIndex >= count($createdImages)) {
                    $imageIndex = 0;
                }
            }
        }

        return $totalImages;
    }

    /**
     * Display success summary
     */
    private function displaySuccessSummary(int $reviewsCreated, int $imagesCreated): void
    {
        $approved = Review::where('is_approved', true)->count();
        $pending = Review::where('is_approved', false)->count();
        $featured = Review::where('is_featured', true)->count();

        $this->info('══════════════════════════════════════════════════════');
        $this->info('  ✅ REVIEWS SUCCESSFULLY CREATED!');
        $this->info('══════════════════════════════════════════════════════');
        $this->newLine();
        $this->line("Total reviews created: {$reviewsCreated}");
        $this->line("├─ ✅ Approved: {$approved}");
        $this->line("├─ ⏳ Pending/Rejected: {$pending}");
        $this->line("├─ ⭐ Featured: {$featured}");
        $this->line("└─ 🖼️  With images: {$imagesCreated} images");
        $this->newLine();

        // Rating distribution
        $this->line('Rating Distribution:');
        for ($rating = 5; $rating >= 1; $rating--) {
            $count = Review::where('rating', $rating)->count();
            if ($count > 0) {
                $stars = str_repeat('⭐', $rating);
                $this->line("   {$stars}: {$count} reviews");
            }
        }

        $this->newLine();
        $this->info('💡 You can now view reviews on product pages and in the admin dashboard.');
    }
}
