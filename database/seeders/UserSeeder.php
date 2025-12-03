<?php

namespace Database\Seeders;

use App\Models\Address;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Admin User
        User::firstOrCreate(
            ['email' => 'admin@duainsan.story'],
            [
                'full_name' => 'Admin Dua Insan',
                'password' => Hash::make('password'),
                'role' => 'admin',
            ]
        );

        // Customer User
        $customer = User::firstOrCreate(
            ['email' => 'customer@example.com'],
            [
                'full_name' => 'Ihza Mahendra Sofyan',
                'password' => Hash::make('password'),
                'phone_number' => '089692070270',
                'role' => 'customer',
            ]
        );

        // Customer Address
        $customer->address()->firstOrCreate(
            ['user_id' => $customer->id],
            [
                'street' => 'Jl. Karet Komp. Surya Kencana 1',
                'city' => 'Kota Pontianak',
                'state' => 'Kalimantan Barat',
                'subdistrict' => 'Pontianak Barat',
                'postal_code' => '71111',
                'country' => 'Indonesia',
            ]
        );
    }
}
