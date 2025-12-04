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
        // Admin Users
        $admins = [
            [
                'full_name' => 'Admin Dua Insan',
                'email' => 'admin@duainsan.story',
                'password' => Hash::make('password'),
                'phone_number' => '081234567890',
                'role' => 'admin',
            ],
            [
                'full_name' => 'Siti Nurhaliza',
                'email' => 'siti.admin@duainsan.story',
                'password' => Hash::make('password'),
                'phone_number' => '081298765432',
                'role' => 'admin',
            ],
        ];

        foreach ($admins as $adminData) {
            User::firstOrCreate(
                ['email' => $adminData['email']],
                $adminData
            );
        }

        // Customer Users with Addresses
        $customers = [
            [
                'user' => [
                    'full_name' => 'Ihza Mahendra Sofyan',
                    'email' => 'customer@example.com',
                    'password' => Hash::make('password'),
                    'phone_number' => '089692070270',
                    'role' => 'customer',
                ],
                'address' => [
                    'street' => 'Jl. Karet Komp. Surya Kencana 1',
                    'city' => 'Kota Pontianak',
                    'state' => 'Kalimantan Barat',
                    'subdistrict' => 'Pontianak Barat',
                    'postal_code' => '71111',
                    'country' => 'Indonesia',
                ],
            ],
            [
                'user' => [
                    'full_name' => 'Budi Santoso',
                    'email' => 'budi.santoso@gmail.com',
                    'password' => Hash::make('password'),
                    'phone_number' => '081234567891',
                    'role' => 'customer',
                ],
                'address' => [
                    'street' => 'Jl. Sudirman No. 123',
                    'city' => 'Jakarta Pusat',
                    'state' => 'DKI Jakarta',
                    'subdistrict' => 'Tanah Abang',
                    'postal_code' => '10220',
                    'country' => 'Indonesia',
                ],
            ],
            [
                'user' => [
                    'full_name' => 'Dewi Lestari',
                    'email' => 'dewi.lestari@yahoo.com',
                    'password' => Hash::make('password'),
                    'phone_number' => '082345678901',
                    'role' => 'customer',
                ],
                'address' => [
                    'street' => 'Jl. Diponegoro No. 45',
                    'city' => 'Bandung',
                    'state' => 'Jawa Barat',
                    'subdistrict' => 'Coblong',
                    'postal_code' => '40132',
                    'country' => 'Indonesia',
                ],
            ],
            [
                'user' => [
                    'full_name' => 'Ahmad Hidayat',
                    'email' => 'ahmad.hidayat@gmail.com',
                    'password' => Hash::make('password'),
                    'phone_number' => '083456789012',
                    'role' => 'customer',
                ],
                'address' => [
                    'street' => 'Jl. Malioboro No. 67',
                    'city' => 'Yogyakarta',
                    'state' => 'DI Yogyakarta',
                    'subdistrict' => 'Gedongtengen',
                    'postal_code' => '55271',
                    'country' => 'Indonesia',
                ],
            ],
            [
                'user' => [
                    'full_name' => 'Rina Kusuma',
                    'email' => 'rina.kusuma@gmail.com',
                    'password' => Hash::make('password'),
                    'phone_number' => '084567890123',
                    'role' => 'customer',
                ],
                'address' => [
                    'street' => 'Jl. Tunjungan No. 89',
                    'city' => 'Surabaya',
                    'state' => 'Jawa Timur',
                    'subdistrict' => 'Genteng',
                    'postal_code' => '60275',
                    'country' => 'Indonesia',
                ],
            ],
            [
                'user' => [
                    'full_name' => 'Faisal Rahman',
                    'email' => 'faisal.rahman@gmail.com',
                    'password' => Hash::make('password'),
                    'phone_number' => '085678901234',
                    'role' => 'customer',
                ],
                'address' => [
                    'street' => 'Jl. Ahmad Yani No. 234',
                    'city' => 'Medan',
                    'state' => 'Sumatera Utara',
                    'subdistrict' => 'Medan Timur',
                    'postal_code' => '20231',
                    'country' => 'Indonesia',
                ],
            ],
            [
                'user' => [
                    'full_name' => 'Siti Aisyah',
                    'email' => 'siti.aisyah@yahoo.com',
                    'password' => Hash::make('password'),
                    'phone_number' => '086789012345',
                    'role' => 'customer',
                ],
                'address' => [
                    'street' => 'Jl. Gatot Subroto No. 56',
                    'city' => 'Semarang',
                    'state' => 'Jawa Tengah',
                    'subdistrict' => 'Semarang Tengah',
                    'postal_code' => '50132',
                    'country' => 'Indonesia',
                ],
            ],
            [
                'user' => [
                    'full_name' => 'Irfan Hakim',
                    'email' => 'irfan.hakim@gmail.com',
                    'password' => Hash::make('password'),
                    'phone_number' => '087890123456',
                    'role' => 'customer',
                ],
                'address' => [
                    'street' => 'Jl. Raya Bogor No. 112',
                    'city' => 'Depok',
                    'state' => 'Jawa Barat',
                    'subdistrict' => 'Pancoran Mas',
                    'postal_code' => '16431',
                    'country' => 'Indonesia',
                ],
            ],
            [
                'user' => [
                    'full_name' => 'Maya Sari',
                    'email' => 'maya.sari@gmail.com',
                    'password' => Hash::make('password'),
                    'phone_number' => '088901234567',
                    'role' => 'customer',
                ],
                'address' => [
                    'street' => 'Jl. Veteran No. 78',
                    'city' => 'Malang',
                    'state' => 'Jawa Timur',
                    'subdistrict' => 'Klojen',
                    'postal_code' => '65111',
                    'country' => 'Indonesia',
                ],
            ],
            [
                'user' => [
                    'full_name' => 'Andi Wijaya',
                    'email' => 'andi.wijaya@gmail.com',
                    'password' => Hash::make('password'),
                    'phone_number' => '089012345678',
                    'role' => 'customer',
                ],
                'address' => [
                    'street' => 'Jl. Metro Tanjung Bunga No. 23',
                    'city' => 'Makassar',
                    'state' => 'Sulawesi Selatan',
                    'subdistrict' => 'Tanjung Bunga',
                    'postal_code' => '90134',
                    'country' => 'Indonesia',
                ],
            ],
            [
                'user' => [
                    'full_name' => 'Lina Marlina',
                    'email' => 'lina.marlina@yahoo.com',
                    'password' => Hash::make('password'),
                    'phone_number' => '081123456789',
                    'role' => 'customer',
                ],
                'address' => [
                    'street' => 'Jl. Sunset Road No. 45',
                    'city' => 'Denpasar',
                    'state' => 'Bali',
                    'subdistrict' => 'Kuta',
                    'postal_code' => '80361',
                    'country' => 'Indonesia',
                ],
            ],
            [
                'user' => [
                    'full_name' => 'Rudi Hermawan',
                    'email' => 'rudi.hermawan@gmail.com',
                    'password' => Hash::make('password'),
                    'phone_number' => '082234567890',
                    'role' => 'customer',
                ],
                'address' => [
                    'street' => 'Jl. Sisingamangaraja No. 90',
                    'city' => 'Palembang',
                    'state' => 'Sumatera Selatan',
                    'subdistrict' => 'Ilir Timur I',
                    'postal_code' => '30114',
                    'country' => 'Indonesia',
                ],
            ],
            [
                'user' => [
                    'full_name' => 'Putri Ayu',
                    'email' => 'putri.ayu@gmail.com',
                    'password' => Hash::make('password'),
                    'phone_number' => '083345678901',
                    'role' => 'customer',
                ],
                'address' => [
                    'street' => 'Jl. Sultan Hasanuddin No. 34',
                    'city' => 'Balikpapan',
                    'state' => 'Kalimantan Timur',
                    'subdistrict' => 'Balikpapan Kota',
                    'postal_code' => '76111',
                    'country' => 'Indonesia',
                ],
            ],
            [
                'user' => [
                    'full_name' => 'Hendra Gunawan',
                    'email' => 'hendra.gunawan@gmail.com',
                    'password' => Hash::make('password'),
                    'phone_number' => '084456789012',
                    'role' => 'customer',
                ],
                'address' => [
                    'street' => 'Jl. Perintis Kemerdekaan No. 56',
                    'city' => 'Samarinda',
                    'state' => 'Kalimantan Timur',
                    'subdistrict' => 'Samarinda Ulu',
                    'postal_code' => '75243',
                    'country' => 'Indonesia',
                ],
            ],
            [
                'user' => [
                    'full_name' => 'Nurul Fadilah',
                    'email' => 'nurul.fadilah@yahoo.com',
                    'password' => Hash::make('password'),
                    'phone_number' => '085567890123',
                    'role' => 'customer',
                ],
                'address' => [
                    'street' => 'Jl. A.H. Nasution No. 78',
                    'city' => 'Tangerang',
                    'state' => 'Banten',
                    'subdistrict' => 'Tangerang Kota',
                    'postal_code' => '15111',
                    'country' => 'Indonesia',
                ],
            ],
        ];

        foreach ($customers as $customerData) {
            $customer = User::firstOrCreate(
                ['email' => $customerData['user']['email']],
                $customerData['user']
            );

            // Create address for customer
            $customer->address()->firstOrCreate(
                ['user_id' => $customer->id],
                $customerData['address']
            );
        }
    }
}
