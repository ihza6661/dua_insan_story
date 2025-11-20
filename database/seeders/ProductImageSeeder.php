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
                        'product_id' => $product->id,
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
            'Buku Tamu 1' => ['guestbook-1/1.jpg', 'guestbook-1/2.jpg', 'guestbook-1/3.jpg', 'guestbook-1/4.jpg', 'guestbook-1/5.jpg'],
            'Buku Tamu 2' => ['guestbook-2/1.jpg', 'guestbook-2/2.jpg', 'guestbook-2/3.jpg'],
            'Buku Tamu 3' => ['guestbook-3/1.jpg', 'guestbook-3/2.jpg', 'guestbook-3/3.jpg', 'guestbook-3/4.jpg', 'guestbook-3/5.jpg', 'guestbook-3/6.jpg'],
            'Buku Tamu 4' => ['guestbook-4/1.jpg', 'guestbook-4/2.jpg', 'guestbook-4/3.jpg', 'guestbook-4/4.jpg', 'guestbook-4/5.jpg'],
            'Buku Tamu 5' => ['guestbook-5/1.jpg', 'guestbook-5/2.jpg'],
            'Buku Tamu 6' => ['guestbook-6/1.jpg', 'guestbook-6/2.jpg', 'guestbook-6/3.jpg', 'guestbook-6/4.jpg'],
            'Buku Tamu 7' => ['guestbook-7/1.jpg', 'guestbook-7/2.jpg', 'guestbook-7/3.jpg'],
            'Buku Tamu 8' => ['guestbook-8/1.jpg', 'guestbook-8/2.jpg', 'guestbook-8/3.jpg', 'guestbook-8/4.jpg'],
            'Produk 1' => ['product-1/1.jpg', 'product-1/2.jpg', 'product-1/3.jpg'],
            'Produk 2' => ['produk-2/1.jpg', 'produk-2/2.jpg', 'produk-2/3.jpg', 'produk-2/4.jpg'],
            'Produk 3' => ['produk-3/1.jpg', 'produk-3/2.jpg', 'produk-3/3.jpg', 'produk-3/4.jpg'],
            'Produk 4' => ['produk-4/1.jpg', 'produk-4/2.jpg', 'produk-4/3.jpg', 'produk-4/4.jpg', 'produk-4/5.jpg'],
            'Produk 5' => ['produk-5/1.jpg', 'produk-5/2.jpg', 'produk-5/3.jpg', 'produk-5/4.jpg'],
            'Produk 6' => ['produk-6/1.jpg', 'produk-6/2.jpg', 'produk-6/3.jpg', 'produk-6/4.jpg', 'produk-6/5.jpg'],
            'Produk 7' => ['produk-7/1.jpg', 'produk-7/2.jpg', 'produk-7/3.jpg', 'produk-7/4.jpg', 'produk-7/5.jpg'],
            'Produk 8' => ['produk-8/1.jpg', 'produk-8/2.jpg', 'produk-8/3.jpg', 'produk-8/4.jpg'],
            'Produk 9' => ['produk-9/1.jpg', 'produk-9/2.jpg', 'produk-9/3.jpg', 'produk-9/4.jpg'],
        ];

        return $productImageMap[$productName] ?? [];
    }
}
