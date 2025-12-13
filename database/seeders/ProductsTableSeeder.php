<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Models\ProductCategory;

class ProductsTableSeeder extends Seeder
{
    /**
     * Seed physical products (printed invitations).
     * Digital products are seeded separately via DigitalProductSeeder.
     *
     * @return void
     */
    public function run()
    {
        Schema::disableForeignKeyConstraints();
        
        // Only delete physical products (preserve digital products if they exist)
        DB::table('products')->where('product_type', 'physical')->orWhereNull('product_type')->delete();

        // Get category ID dynamically to avoid hardcoding
        $printedCategoryId = ProductCategory::where('slug', 'undangan-cetak')->first()->id;

        DB::table('products')->insert([
            0 => [
                'id' => 1,
                'category_id' => $printedCategoryId,
                'product_type' => 'physical',
                'template_id' => null,
                'name' => 'Undangan Cetak 1',
                'slug' => 'undangan-cetak-1',
                'description' => 'Undangan cetak dengan desain elegan untuk acara pernikahan',
                'base_price' => 2500,
                'weight' => 50,
                'min_order_quantity' => 100,
                'is_active' => 1,
                'created_at' => '2025-11-16 13:29:32',
                'updated_at' => '2025-11-16 13:29:32',
            ],
            1 => [
                'id' => 2,
                'category_id' => $printedCategoryId,
                'product_type' => 'physical',
                'template_id' => null,
                'name' => 'Undangan Cetak 2',
                'slug' => 'undangan-cetak-2',
                'description' => 'Undangan cetak dengan desain klasik untuk acara pernikahan',
                'base_price' => 2500,
                'weight' => 50,
                'min_order_quantity' => 100,
                'is_active' => 1,
                'created_at' => '2025-11-18 14:00:00',
                'updated_at' => '2025-11-18 14:00:00',
            ],
            2 => [
                'id' => 3,
                'category_id' => $printedCategoryId,
                'product_type' => 'physical',
                'template_id' => null,
                'name' => 'Undangan Cetak 3',
                'slug' => 'undangan-cetak-3',
                'description' => 'Undangan cetak dengan desain modern untuk acara pernikahan',
                'base_price' => 2500,
                'weight' => 50,
                'min_order_quantity' => 100,
                'is_active' => 1,
                'created_at' => '2025-11-18 14:00:00',
                'updated_at' => '2025-11-18 14:00:00',
            ],
            3 => [
                'id' => 4,
                'category_id' => $printedCategoryId,
                'product_type' => 'physical',
                'template_id' => null,
                'name' => 'Undangan Cetak 4',
                'slug' => 'undangan-cetak-4',
                'description' => 'Undangan cetak dengan desain minimalis untuk acara pernikahan',
                'base_price' => 2500,
                'weight' => 50,
                'min_order_quantity' => 100,
                'is_active' => 1,
                'created_at' => '2025-11-18 14:00:00',
                'updated_at' => '2025-11-18 14:00:00',
            ],
            4 => [
                'id' => 5,
                'category_id' => $printedCategoryId,
                'product_type' => 'physical',
                'template_id' => null,
                'name' => 'Undangan Cetak 5',
                'slug' => 'undangan-cetak-5',
                'description' => 'Undangan cetak dengan desain premium untuk acara pernikahan',
                'base_price' => 2500,
                'weight' => 50,
                'min_order_quantity' => 100,
                'is_active' => 1,
                'created_at' => '2025-11-18 14:00:00',
                'updated_at' => '2025-11-18 14:00:00',
            ],
            5 => [
                'id' => 6,
                'category_id' => $printedCategoryId,
                'product_type' => 'physical',
                'template_id' => null,
                'name' => 'Undangan Cetak 6',
                'slug' => 'undangan-cetak-6',
                'description' => 'Undangan cetak dengan desain tradisional untuk acara pernikahan',
                'base_price' => 2500,
                'weight' => 50,
                'min_order_quantity' => 100,
                'is_active' => 1,
                'created_at' => '2025-11-18 14:00:00',
                'updated_at' => '2025-11-18 14:00:00',
            ],
            6 => [
                'id' => 7,
                'category_id' => $printedCategoryId,
                'product_type' => 'physical',
                'template_id' => null,
                'name' => 'Undangan Cetak 7',
                'slug' => 'undangan-cetak-7',
                'description' => 'Undangan cetak dengan desain floral untuk acara pernikahan',
                'base_price' => 2500,
                'weight' => 50,
                'min_order_quantity' => 100,
                'is_active' => 1,
                'created_at' => '2025-11-18 14:00:00',
                'updated_at' => '2025-11-18 14:00:00',
            ],
            7 => [
                'id' => 8,
                'category_id' => $printedCategoryId,
                'product_type' => 'physical',
                'template_id' => null,
                'name' => 'Undangan Cetak 8',
                'slug' => 'undangan-cetak-8',
                'description' => 'Undangan cetak dengan desain mewah untuk acara pernikahan',
                'base_price' => 2500,
                'weight' => 50,
                'min_order_quantity' => 100,
                'is_active' => 1,
                'created_at' => '2025-11-18 14:00:00',
                'updated_at' => '2025-11-18 14:00:00',
            ],
            8 => [
                'id' => 9,
                'category_id' => $printedCategoryId,
                'product_type' => 'physical',
                'template_id' => null,
                'name' => 'Undangan Cetak 9',
                'slug' => 'undangan-cetak-9',
                'description' => 'Undangan cetak dengan desain rustic untuk acara pernikahan',
                'base_price' => 2500,
                'weight' => 50,
                'min_order_quantity' => 100,
                'is_active' => 1,
                'created_at' => '2025-11-18 14:00:00',
                'updated_at' => '2025-11-18 14:00:00',
            ],
        ]);
        
        // Reset auto-increment to continue from the highest ID
        $maxId = DB::table('products')->max('id') ?? 0;
        DB::statement("ALTER TABLE products AUTO_INCREMENT = " . ($maxId + 1));
        
        Schema::enableForeignKeyConstraints();

    }
}
