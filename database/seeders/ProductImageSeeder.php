<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ProductImageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Schema::disableForeignKeyConstraints();
        DB::table('product_images')->truncate();
        $products = Product::with('variants.images')->get();

        foreach ($products as $product) {
            // Skip digital products (they have their own images)
            if ($product->product_type === 'digital') {
                continue;
            }

            // Check if product already has images
            if ($product->variants->flatMap->images->isNotEmpty()) {
                continue;
            }

            // Get product variant (if exists)
            $variantId = $product->variants->first()?->id;

            $productImages = $this->getProductImagesForProduct($product->name);

            if (empty($productImages)) {
                // No images mapped for this product
                continue;
            }

            foreach ($productImages as $image) {
                ProductImage::create([
                    'product_id' => $product->id,
                    'product_variant_id' => $variantId, // Can be null
                    'image' => 'product-images/'.$image,
                    'alt_text' => $product->name,
                    'is_featured' => true,
                ]);
            }
        }
        Schema::enableForeignKeyConstraints();
    }

    private function getProductImagesForProduct(string $productName): array
    {
        $productImageMap = [
            'Undangan Cetak 1' => ['undangan-cetak-1/1.jpg', 'undangan-cetak-1/2.jpg', 'undangan-cetak-1/3.jpg', 'undangan-cetak-1/4.jpg', 'undangan-cetak-1/5.jpg'],
            'Undangan Cetak 2' => ['undangan-cetak-2/1.jpg', 'undangan-cetak-2/2.jpg', 'undangan-cetak-2/3.jpg'],
            'Undangan Cetak 3' => ['undangan-cetak-3/1.jpg', 'undangan-cetak-3/2.jpg', 'undangan-cetak-3/3.jpg', 'undangan-cetak-3/4.jpg', 'undangan-cetak-3/5.jpg', 'undangan-cetak-3/6.jpg'],
            'Undangan Cetak 4' => ['undangan-cetak-4/1.jpg', 'undangan-cetak-4/2.jpg', 'undangan-cetak-4/3.jpg', 'undangan-cetak-4/4.jpg', 'undangan-cetak-4/5.jpg'],
            'Undangan Cetak 5' => ['undangan-cetak-5/1.jpg', 'undangan-cetak-5/2.jpg'],
            'Undangan Cetak 6' => ['undangan-cetak-6/1.jpg', 'undangan-cetak-6/2.jpg', 'undangan-cetak-6/3.jpg', 'undangan-cetak-6/4.jpg'],
            'Undangan Cetak 7' => ['undangan-cetak-7/1.jpg', 'undangan-cetak-7/2.jpg', 'undangan-cetak-7/3.jpg'],
            'Undangan Cetak 8' => ['undangan-cetak-8/1.jpg', 'undangan-cetak-8/2.jpg', 'undangan-cetak-8/3.jpg', 'undangan-cetak-8/4.jpg'],
            'Undangan Cetak 9' => ['undangan-cetak-9/1.jpg', 'undangan-cetak-9/2.jpg', 'undangan-cetak-9/3.jpg'],
            'Undangan Digital 1' => ['undangan-digital-1/1.jpg', 'undangan-digital-1/2.jpg', 'undangan-digital-1/3.jpg', 'undangan-digital-1/4.jpg'],
            'Undangan Digital 2' => ['undangan-digital-2/1.jpg', 'undangan-digital-2/2.jpg', 'undangan-digital-2/3.jpg', 'undangan-digital-2/4.jpg'],
            'Undangan Digital 3' => ['undangan-digital-3/1.jpg', 'undangan-digital-3/2.jpg', 'undangan-digital-3/3.jpg', 'undangan-digital-3/4.jpg', 'undangan-digital-3/5.jpg'],
            'Undangan Digital 4' => ['undangan-digital-4/1.jpg', 'undangan-digital-4/2.jpg', 'undangan-digital-4/3.jpg', 'undangan-digital-4/4.jpg'],
            'Undangan Digital 5' => ['undangan-digital-5/1.jpg', 'undangan-digital-5/2.jpg', 'undangan-digital-5/3.jpg', 'undangan-digital-5/4.jpg', 'undangan-digital-5/5.jpg'],
            'Undangan Digital 6' => ['undangan-digital-6/1.jpg', 'undangan-digital-6/2.jpg', 'undangan-digital-6/3.jpg', 'undangan-digital-6/4.jpg', 'undangan-digital-6/5.jpg'],
            'Undangan Digital 7' => ['undangan-digital-7/1.jpg', 'undangan-digital-7/2.jpg', 'undangan-digital-7/3.jpg', 'undangan-digital-7/4.jpg'],
            'Undangan Digital 8' => ['undangan-digital-8/1.jpg', 'undangan-digital-8/2.jpg', 'undangan-digital-8/3.jpg', 'undangan-digital-8/4.jpg'],
        ];

        return $productImageMap[$productName] ?? [];
    }
}
