<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class ProductsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        
        Schema::disableForeignKeyConstraints();
        \DB::table('products')->truncate();
        
        \DB::table('products')->insert(array (
            0 =>
            array (
                'id' => 1,
                'category_id' => 2,
                'name' => 'Buku Tamu 1',
                'description' => '',
                'base_price' => 10000,
                'min_order_quantity' => 100,
                'is_active' => 1,
                'created_at' => '2025-11-16 13:29:32',
                'updated_at' => '2025-11-16 13:29:32',
            ),
            1 =>
            array (
                'id' => 2,
                'category_id' => 2,
                'name' => 'Buku Tamu 2',
                'description' => '',
                'base_price' => 10000,
                'min_order_quantity' => 100,
                'is_active' => 1,
                'created_at' => '2025-11-18 14:00:00',
                'updated_at' => '2025-11-18 14:00:00',
            ),
            2 =>
            array (
                'id' => 3,
                'category_id' => 2,
                'name' => 'Buku Tamu 3',
                'description' => '',
                'base_price' => 10000,
                'min_order_quantity' => 100,
                'is_active' => 1,
                'created_at' => '2025-11-18 14:00:00',
                'updated_at' => '2025-11-18 14:00:00',
            ),
            3 =>
            array (
                'id' => 4,
                'category_id' => 1,
                'name' => 'Produk 1',
                'description' => '',
                'base_price' => 1500,
                'min_order_quantity' => 100,
                'is_active' => 1,
                'created_at' => '2025-11-18 14:00:00',
                'updated_at' => '2025-11-18 14:00:00',
            ),
            4 =>
            array (
                'id' => 5,
                'category_id' => 2,
                'name' => 'Buku Tamu 4',
                'description' => 'Buku tamu untuk acara pernikahan',
                'base_price' => 10000,
                'min_order_quantity' => 100,
                'is_active' => 1,
                'created_at' => '2025-11-18 14:00:00',
                'updated_at' => '2025-11-18 14:00:00',
            ),
            5 =>
            array (
                'id' => 6,
                'category_id' => 2,
                'name' => 'Buku Tamu 5',
                'description' => 'Buku tamu untuk acara pernikahan',
                'base_price' => 10000,
                'min_order_quantity' => 100,
                'is_active' => 1,
                'created_at' => '2025-11-18 14:00:00',
                'updated_at' => '2025-11-18 14:00:00',
            ),
            6 =>
            array (
                'id' => 7,
                'category_id' => 2,
                'name' => 'Buku Tamu 6',
                'description' => 'Buku tamu untuk acara pernikahan',
                'base_price' => 10000,
                'min_order_quantity' => 100,
                'is_active' => 1,
                'created_at' => '2025-11-18 14:00:00',
                'updated_at' => '2025-11-18 14:00:00',
            ),
            7 =>
            array (
                'id' => 8,
                'category_id' => 2,
                'name' => 'Buku Tamu 7',
                'description' => 'Buku tamu untuk acara pernikahan',
                'base_price' => 10000,
                'min_order_quantity' => 100,
                'is_active' => 1,
                'created_at' => '2025-11-18 14:00:00',
                'updated_at' => '2025-11-18 14:00:00',
            ),
            8 =>
            array (
                'id' => 9,
                'category_id' => 2,
                'name' => 'Buku Tamu 8',
                'description' => 'Buku tamu untuk acara pernikahan',
                'base_price' => 10000,
                'min_order_quantity' => 100,
                'is_active' => 1,
                'created_at' => '2025-11-18 14:00:00',
                'updated_at' => '2025-11-18 14:00:00',
            ),
            9 =>
            array (
                'id' => 10,
                'category_id' => 1,
                'name' => 'Produk 2',
                'description' => '',
                'base_price' => 1500,
                'min_order_quantity' => 100,
                'is_active' => 1,
                'created_at' => '2025-11-18 14:00:00',
                'updated_at' => '2025-11-18 14:00:00',
            ),
            10 =>
            array (
                'id' => 11,
                'category_id' => 1,
                'name' => 'Produk 3',
                'description' => '',
                'base_price' => 1500,
                'min_order_quantity' => 100,
                'is_active' => 1,
                'created_at' => '2025-11-18 14:00:00',
                'updated_at' => '2025-11-18 14:00:00',
            ),
            11 =>
            array (
                'id' => 12,
                'category_id' => 1,
                'name' => 'Produk 4',
                'description' => '',
                'base_price' => 1500,
                'min_order_quantity' => 100,
                'is_active' => 1,
                'created_at' => '2025-11-18 14:00:00',
                'updated_at' => '2025-11-18 14:00:00',
            ),
            12 =>
            array (
                'id' => 13,
                'category_id' => 1,
                'name' => 'Produk 5',
                'description' => '',
                'base_price' => 1500,
                'min_order_quantity' => 100,
                'is_active' => 1,
                'created_at' => '2025-11-18 14:00:00',
                'updated_at' => '2025-11-18 14:00:00',
            ),
            13 =>
            array (
                'id' => 14,
                'category_id' => 1,
                'name' => 'Produk 6',
                'description' => '',
                'base_price' => 1500,
                'min_order_quantity' => 100,
                'is_active' => 1,
                'created_at' => '2025-11-18 14:00:00',
                'updated_at' => '2025-11-18 14:00:00',
            ),
            14 =>
            array (
                'id' => 15,
                'category_id' => 1,
                'name' => 'Produk 7',
                'description' => '',
                'base_price' => 1500,
                'min_order_quantity' => 100,
                'is_active' => 1,
                'created_at' => '2025-11-18 14:00:00',
                'updated_at' => '2025-11-18 14:00:00',
            ),
            15 =>
            array (
                'id' => 16,
                'category_id' => 1,
                'name' => 'Produk 8',
                'description' => '',
                'base_price' => 1500,
                'min_order_quantity' => 100,
                'is_active' => 1,
                'created_at' => '2025-11-18 14:00:00',
                'updated_at' => '2025-11-18 14:00:00',
            ),
            16 =>
            array (
                'id' => 17,
                'category_id' => 1,
                'name' => 'Produk 9',
                'description' => '',
                'base_price' => 1500,
                'min_order_quantity' => 100,
                'is_active' => 1,
                'created_at' => '2025-11-18 14:00:00',
                'updated_at' => '2025-11-18 14:00:00',
            ),
        ));
        Schema::enableForeignKeyConstraints();
        
        
    }
}