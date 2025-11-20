<?php

namespace Database\Seeders;

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
        User::firstOrCreate(
            ['email' => 'customer@example.com'],
            [
                'full_name' => 'Pelanggan Setia',
                'password' => Hash::make('password'),
                'role' => 'customer',
            ]
        );
    }
}
