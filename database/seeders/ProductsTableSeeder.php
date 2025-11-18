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