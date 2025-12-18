<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
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
        ]);
        
        // Reset auto-increment/sequence to continue from the highest ID
        $maxId = DB::table('product_variants')->max('id') ?? 0;
        $driver = DB::connection()->getDriverName();
        
        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE product_variants AUTO_INCREMENT = " . ($maxId + 1));
        } elseif ($driver === 'pgsql') {
            DB::statement("SELECT setval(pg_get_serial_sequence('product_variants', 'id'), ?, false)", [$maxId + 1]);
        }
        
        Schema::enableForeignKeyConstraints();

    }
}
