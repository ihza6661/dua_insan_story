<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            AdminUserSeeder::class,
            ProductCategorySeeder::class,
            AddOnSeeder::class,
            AttributeSeeder::class,

            // Seeders generated from current database
            ProductsTableSeeder::class,
            ProductVariantsTableSeeder::class,
            ProductAddOnsTableSeeder::class,
            ProductVariantOptionsTableSeeder::class,
            ProductImageSeeder::class,

            // Order and Review seeders
            OrderSeeder::class,
            InvitationDetailSeeder::class,
            ReviewSeeder::class,
            ReviewImageSeeder::class,

            // Promo Code seeder
            PromoCodeSeeder::class,
        ]);
    }
}
