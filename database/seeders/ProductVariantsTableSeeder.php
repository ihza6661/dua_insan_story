<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class ProductVariantsTableSeeder extends Seeder
{
    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {

        Schema::disableForeignKeyConstraints();
        \DB::table('product_variants')->truncate();

        \DB::table('product_variants')->insert([
            0 => [
                'id' => 1,
                'product_id' => 1,
                'price' => 10000,
                'stock' => 100,
                'created_at' => '2025-11-16 13:29:48',
                'updated_at' => '2025-11-16 13:29:48',
            ],
            1 => [
                'id' => 2,
                'product_id' => 2,
                'price' => 10000,
                'stock' => 50,
                'created_at' => '2025-11-18 14:00:00',
                'updated_at' => '2025-11-18 14:00:00',
            ],
            2 => [
                'id' => 3,
                'product_id' => 3,
                'price' => 10000,
                'stock' => 75,
                'created_at' => '2025-11-18 14:00:00',
                'updated_at' => '2025-11-18 14:00:00',
            ],
            3 => [
                'id' => 4,
                'product_id' => 4,
                'price' => 1500,
                'stock' => 100,
                'created_at' => '2025-11-18 14:00:00',
                'updated_at' => '2025-11-18 14:00:00',
            ],
            4 => [
                'id' => 5,
                'product_id' => 5,
                'price' => 10000,
                'stock' => 100,
                'created_at' => '2025-11-18 14:00:00',
                'updated_at' => '2025-11-18 14:00:00',
            ],
            5 => [
                'id' => 6,
                'product_id' => 6,
                'price' => 10000,
                'stock' => 100,
                'created_at' => '2025-11-18 14:00:00',
                'updated_at' => '2025-11-18 14:00:00',
            ],
            6 => [
                'id' => 7,
                'product_id' => 7,
                'price' => 10000,
                'stock' => 100,
                'created_at' => '2025-11-18 14:00:00',
                'updated_at' => '2025-11-18 14:00:00',
            ],
            7 => [
                'id' => 8,
                'product_id' => 8,
                'price' => 10000,
                'stock' => 100,
                'created_at' => '2025-11-18 14:00:00',
                'updated_at' => '2025-11-18 14:00:00',
            ],
            8 => [
                'id' => 9,
                'product_id' => 9,
                'price' => 10000,
                'stock' => 100,
                'created_at' => '2025-11-18 14:00:00',
                'updated_at' => '2025-11-18 14:00:00',
            ],
            9 => [
                'id' => 10,
                'product_id' => 10,
                'price' => 1500,
                'stock' => 100,
                'created_at' => '2025-11-18 14:00:00',
                'updated_at' => '2025-11-18 14:00:00',
            ],
            10 => [
                'id' => 11,
                'product_id' => 11,
                'price' => 1500,
                'stock' => 100,
                'created_at' => '2025-11-18 14:00:00',
                'updated_at' => '2025-11-18 14:00:00',
            ],
            11 => [
                'id' => 12,
                'product_id' => 12,
                'price' => 1500,
                'stock' => 100,
                'created_at' => '2025-11-18 14:00:00',
                'updated_at' => '2025-11-18 14:00:00',
            ],
            12 => [
                'id' => 13,
                'product_id' => 13,
                'price' => 1500,
                'stock' => 100,
                'created_at' => '2025-11-18 14:00:00',
                'updated_at' => '2025-11-18 14:00:00',
            ],
            13 => [
                'id' => 14,
                'product_id' => 14,
                'price' => 1500,
                'stock' => 100,
                'created_at' => '2025-11-18 14:00:00',
                'updated_at' => '2025-11-18 14:00:00',
            ],
            14 => [
                'id' => 15,
                'product_id' => 15,
                'price' => 1500,
                'stock' => 100,
                'created_at' => '2025-11-18 14:00:00',
                'updated_at' => '2025-11-18 14:00:00',
            ],
            15 => [
                'id' => 16,
                'product_id' => 16,
                'price' => 1500,
                'stock' => 100,
                'created_at' => '2025-11-18 14:00:00',
                'updated_at' => '2025-11-18 14:00:00',
            ],
            16 => [
                'id' => 17,
                'product_id' => 17,
                'price' => 1500,
                'stock' => 100,
                'created_at' => '2025-11-18 14:00:00',
                'updated_at' => '2025-11-18 14:00:00',
            ],
        ]);
        Schema::enableForeignKeyConstraints();

    }
}
