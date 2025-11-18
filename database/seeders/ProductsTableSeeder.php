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
                'category_id' => 1,
                'name' => 'Blue on Blue',
                'description' => NULL,
                'base_price' => 1500,
                'min_order_quantity' => 100,
                'is_active' => 1,
                'created_at' => '2025-10-10 12:00:47',
                'updated_at' => '2025-10-11 13:18:12',
            ),
            1 => 
            array (
                'id' => 2,
                'category_id' => 1,
                'name' => 'Alice in Wonderland',
                'description' => NULL,
                'base_price' => 2000,
                'min_order_quantity' => 100,
                'is_active' => 1,
                'created_at' => '2025-10-10 12:00:47',
                'updated_at' => '2025-10-11 13:22:39',
            ),
            2 => 
            array (
                'id' => 3,
                'category_id' => 1,
                'name' => 'Tema Jawa Modern',
                'description' => NULL,
                'base_price' => 2500,
                'min_order_quantity' => 1000,
                'is_active' => 1,
                'created_at' => '2025-10-11 13:28:08',
                'updated_at' => '2025-10-11 13:28:08',
            ),
            3 => 
            array (
                'id' => 4,
                'category_id' => 1,
                'name' => 'Fairy Tale',
                'description' => NULL,
                'base_price' => 2000,
                'min_order_quantity' => 1000,
                'is_active' => 1,
                'created_at' => '2025-10-11 13:29:32',
                'updated_at' => '2025-10-11 13:29:32',
            ),
            4 =>
            array (
                'id' => 5,
                'category_id' => 2,
                'name' => 'Guest Book',
                'description' => 'Buku tamu untuk acara pernikahan',
                'base_price' => 2000,
                'min_order_quantity' => 100,
                'is_active' => 1,
                'created_at' => '2025-11-16 13:29:32',
                'updated_at' => '2025-11-16 13:29:32',
            ),
        ));
        Schema::enableForeignKeyConstraints();
        
        
    }
}