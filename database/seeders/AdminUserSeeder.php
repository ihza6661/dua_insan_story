<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'full_name' => 'Admin User',
                'password' => Hash::make('password'),
                'phone_number' => '1234567890',
                'role' => 'admin',
            ]
        );
    }
}
