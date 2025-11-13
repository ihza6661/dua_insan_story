<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductAddOnsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

    DB::table('product_add_ons')->delete();
        
    DB::table('product_add_ons')->insert(array (
            0 => 
            array (
                'product_id' => 1,
                'add_on_id' => 1,
                'weight' => null,
            ),
            1 => 
            array (
                'product_id' => 2,
                'add_on_id' => 1,
                'weight' => null,
            ),
            2 => 
            array (
                'product_id' => 1,
                'add_on_id' => 2,
                'weight' => null,
            ),
            3 => 
            array (
                'product_id' => 2,
                'add_on_id' => 2,
                'weight' => null,
            ),
        ));
        
        
    }
}