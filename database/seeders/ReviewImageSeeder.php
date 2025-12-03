<?php

namespace Database\Seeders;

use App\Models\Review;
use App\Models\ReviewImage;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class ReviewImageSeeder extends Seeder
{
    public function run(): void
    {
        // Get approved 5-star reviews to add images
        $reviews = Review::where('is_approved', true)
            ->where('rating', 5)
            ->take(5)
            ->get();

        if ($reviews->count() === 0) {
            $this->command->warn('No approved 5-star reviews found. Skipping image seeding.');

            return;
        }

        $storagePath = storage_path('app/public/review-images');

        // Ensure directory exists
        if (! File::exists($storagePath)) {
            File::makeDirectory($storagePath, 0755, true);
        }

        // Create placeholder images (1x1 pixel colored images)
        $imageColors = [
            ['name' => 'invitation-sample-1.jpg', 'color' => [255, 200, 200]], // Light pink
            ['name' => 'invitation-sample-2.jpg', 'color' => [200, 220, 255]], // Light blue
            ['name' => 'invitation-sample-3.jpg', 'color' => [255, 240, 200]], // Light gold
            ['name' => 'invitation-sample-4.jpg', 'color' => [220, 255, 220]], // Light green
            ['name' => 'guestbook-sample-1.jpg', 'color' => [255, 220, 255]], // Light purple
            ['name' => 'guestbook-sample-2.jpg', 'color' => [255, 230, 200]], // Peach
            ['name' => 'product-detail-1.jpg', 'color' => [200, 240, 255]], // Sky blue
            ['name' => 'product-detail-2.jpg', 'color' => [255, 255, 200]], // Light yellow
        ];

        $createdImages = [];

        // Create actual placeholder image files using GD
        foreach ($imageColors as $imageData) {
            $filename = $imageData['name'];
            $filepath = $storagePath.'/'.$filename;

            // Create a small placeholder image (500x500 px)
            if (function_exists('imagecreatetruecolor')) {
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
        }

        if (empty($createdImages)) {
            $this->command->warn('Could not create placeholder images. GD extension may not be available.');

            return;
        }

        // Attach images to reviews
        $imageIndex = 0;
        foreach ($reviews as $review) {
            // Add 2-4 images per review
            $imageCount = rand(2, 4);

            for ($i = 0; $i < $imageCount && $imageIndex < count($createdImages); $i++) {
                ReviewImage::create([
                    'review_id' => $review->id,
                    'image_path' => 'review-images/'.$createdImages[$imageIndex],
                    'alt_text' => 'Sample review image '.($i + 1),
                    'display_order' => $i + 1,
                ]);

                $imageIndex++;

                // Reset index if we run out of images
                if ($imageIndex >= count($createdImages)) {
                    $imageIndex = 0;
                }
            }

            $this->command->info("Added {$imageCount} images to review #{$review->id}");
        }

        $this->command->info("\n✅ Successfully created sample review images!");
        $this->command->info('   - Total image files: '.count($createdImages));
        $this->command->info('   - Reviews with images: '.$reviews->count());
    }
}
