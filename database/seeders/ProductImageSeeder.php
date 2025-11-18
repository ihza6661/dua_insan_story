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
        \DB::table('product_images')->truncate();
        $products = Product::with('variants.images')->get();

        foreach ($products as $product) {
            if ($product->variants->flatMap->images->isEmpty()) {
                $productImages = $this->getProductImagesForProduct($product->name);

                foreach ($productImages as $image) {
                    ProductImage::create([
                        'product_variant_id' => $product->variants->first()->id,
                        'image' => 'product-images/' . $image,
                        'alt_text' => $product->name,
                        'is_featured' => true,
                    ]);
                }
            }
        }
        Schema::enableForeignKeyConstraints();
    }

    private function getProductImagesForProduct(string $productName): array
    {
        $productImageMap = [
            'Guest Book' => ['guestbook-1/duainsan.story-16-11-2025-0001.jpg', 'guestbook-1/duainsan.story-16-11-2025-0002.jpg', 'guestbook-1/duainsan.story-16-11-2025-0003.jpg', 'guestbook-1/duainsan.story-16-11-2025-0004.jpg', 'guestbook-1/duainsan.story-16-11-2025-0005.jpg', 'guestbook-1/duainsan.story-16-11-2025-0006.jpg', 'guestbook-1/duainsan.story-16-11-2025-0007.jpg', 'guestbook-1/duainsan.story-16-11-2025-0008.jpg'],
        ];

        return $productImageMap[$productName] ?? [];
    }
}
