<?php

namespace Database\Seeders;

use App\Models\ProductCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProductCategorySeeder extends Seeder
{
    public function run(): void
    {
        // Delete old categories if they exist
        ProductCategory::whereIn('slug', ['undangan-pernikahan', 'buku-tamu'])->delete();

        ProductCategory::updateOrCreate([
            'slug' => Str::slug('Undangan Cetak'),
        ], [
            'name' => 'Undangan Cetak',
            'image' => 'category-images/wedding-print.jpg',
        ]);

        ProductCategory::updateOrCreate([
            'slug' => Str::slug('Undangan Digital'),
        ], [
            'name' => 'Undangan Digital',
            'image' => 'category-images/wedding-digital.jpg',
        ]);
    }
}
