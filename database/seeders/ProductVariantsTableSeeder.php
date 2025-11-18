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
        
        \DB::table('product_variants')->insert(array (
            0 =>
            array (
                'id' => 1,
                'product_id' => 1,
                'price' => 2000,
                'stock' => 100,
                'created_at' => '2025-11-16 13:29:48',
                'updated_at' => '2025-11-16 13:29:48',
            ),
        ));
        Schema::enableForeignKeyConstraints();
        
        
    }
}