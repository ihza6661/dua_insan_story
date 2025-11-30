<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class ProductVariantOptionsTableSeeder extends Seeder
{
    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {

        \DB::table('product_variant_options')->delete();

        \DB::table('product_variant_options')->insert([
            0 => [
                'product_variant_id' => 1,
                'attribute_value_id' => 1,
            ],
            1 => [
                'product_variant_id' => 2,
                'attribute_value_id' => 2,
            ],
            2 => [
                'product_variant_id' => 3,
                'attribute_value_id' => 3,
            ],
            3 => [
                'product_variant_id' => 5,
                'attribute_value_id' => 3,
            ],
            4 => [
                'product_variant_id' => 4,
                'attribute_value_id' => 4,
            ],
            5 => [
                'product_variant_id' => 6,
                'attribute_value_id' => 4,
            ],
            6 => [
                'product_variant_id' => 1,
                'attribute_value_id' => 5,
            ],
            7 => [
                'product_variant_id' => 2,
                'attribute_value_id' => 5,
            ],
            8 => [
                'product_variant_id' => 5,
                'attribute_value_id' => 5,
            ],
            9 => [
                'product_variant_id' => 6,
                'attribute_value_id' => 5,
            ],
            10 => [
                'product_variant_id' => 3,
                'attribute_value_id' => 6,
            ],
            11 => [
                'product_variant_id' => 4,
                'attribute_value_id' => 6,
            ],
        ]);

    }
}
