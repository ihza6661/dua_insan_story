<?php

namespace Database\Seeders;

use App\Models\AddOn;
use Illuminate\Database\Seeder;

class AddOnSeeder extends Seeder
{
    public function run(): void
    {
        $addOns = [
            ['name' => 'Denah Lokasi', 'price' => 1000],
            ['name' => 'Kupon Souvenir', 'price' => 300],
            ['name' => 'Amplop Fancy', 'price' => 1200],
            ['name' => 'Pita Satin', 'price' => 500],
            ['name' => 'Tali Rami', 'price' => 300],
        ];

        foreach ($addOns as $addOn) {
            AddOn::firstOrCreate(
                ['name' => $addOn['name']],
                $addOn
            );
        }
    }
}
