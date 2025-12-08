<?php

namespace Database\Seeders;

use App\Models\InvitationTemplate;
use App\Models\TemplateField;
use Illuminate\Database\Seeder;

class TemplateFieldSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get the Sakeenah template
        $sakeenah = InvitationTemplate::where('slug', 'sakeenah-islamic-modern')->first();
        
        if (!$sakeenah) {
            $this->command->warn('⚠️  Sakeenah template not found. Run InvitationTemplateSeeder first.');
            return;
        }

        // Couple Information Fields
        $fields = [
            // Bride Information
            [
                'template_id' => $sakeenah->id,
                'field_key' => 'bride_full_name',
                'field_label' => 'Nama Lengkap Pengantin Wanita',
                'field_type' => 'text',
                'field_category' => 'couple',
                'placeholder' => 'contoh: Siti Aisyah binti Abdullah',
                'default_value' => null,
                'help_text' => 'Gunakan nama lengkap sesuai KTP',
                'validation_rules' => [
                    'required' => true,
                    'min' => 3,
                    'max' => 100,
                ],
                'display_order' => 1,
                'is_active' => true,
            ],
            [
                'template_id' => $sakeenah->id,
                'field_key' => 'bride_nickname',
                'field_label' => 'Nama Panggilan Pengantin Wanita',
                'field_type' => 'text',
                'field_category' => 'couple',
                'placeholder' => 'contoh: Aisyah',
                'default_value' => null,
                'help_text' => 'Nama yang biasa dipanggil',
                'validation_rules' => [
                    'required' => true,
                    'min' => 2,
                    'max' => 50,
                ],
                'display_order' => 2,
                'is_active' => true,
            ],
            [
                'template_id' => $sakeenah->id,
                'field_key' => 'bride_parents',
                'field_label' => 'Nama Orang Tua Pengantin Wanita',
                'field_type' => 'text',
                'field_category' => 'couple',
                'placeholder' => 'contoh: Bapak Abdullah & Ibu Fatimah',
                'default_value' => null,
                'help_text' => 'Format: Bapak [Nama] & Ibu [Nama]',
                'validation_rules' => [
                    'required' => true,
                    'min' => 5,
                    'max' => 150,
                ],
                'display_order' => 3,
                'is_active' => true,
            ],

            // Groom Information
            [
                'template_id' => $sakeenah->id,
                'field_key' => 'groom_full_name',
                'field_label' => 'Nama Lengkap Pengantin Pria',
                'field_type' => 'text',
                'field_category' => 'couple',
                'placeholder' => 'contoh: Muhammad Ahmad bin Umar',
                'default_value' => null,
                'help_text' => 'Gunakan nama lengkap sesuai KTP',
                'validation_rules' => [
                    'required' => true,
                    'min' => 3,
                    'max' => 100,
                ],
                'display_order' => 4,
                'is_active' => true,
            ],
            [
                'template_id' => $sakeenah->id,
                'field_key' => 'groom_nickname',
                'field_label' => 'Nama Panggilan Pengantin Pria',
                'field_type' => 'text',
                'field_category' => 'couple',
                'placeholder' => 'contoh: Ahmad',
                'default_value' => null,
                'help_text' => 'Nama yang biasa dipanggil',
                'validation_rules' => [
                    'required' => true,
                    'min' => 2,
                    'max' => 50,
                ],
                'display_order' => 5,
                'is_active' => true,
            ],
            [
                'template_id' => $sakeenah->id,
                'field_key' => 'groom_parents',
                'field_label' => 'Nama Orang Tua Pengantin Pria',
                'field_type' => 'text',
                'field_category' => 'couple',
                'placeholder' => 'contoh: Bapak Umar & Ibu Khadijah',
                'default_value' => null,
                'help_text' => 'Format: Bapak [Nama] & Ibu [Nama]',
                'validation_rules' => [
                    'required' => true,
                    'min' => 5,
                    'max' => 150,
                ],
                'display_order' => 6,
                'is_active' => true,
            ],

            // Akad Event Details
            [
                'template_id' => $sakeenah->id,
                'field_key' => 'akad_date',
                'field_label' => 'Tanggal Akad',
                'field_type' => 'date',
                'field_category' => 'event',
                'placeholder' => null,
                'default_value' => null,
                'help_text' => 'Pilih tanggal pelaksanaan akad nikah',
                'validation_rules' => [
                    'required' => true,
                ],
                'display_order' => 7,
                'is_active' => true,
            ],
            [
                'template_id' => $sakeenah->id,
                'field_key' => 'akad_time',
                'field_label' => 'Waktu Akad',
                'field_type' => 'time',
                'field_category' => 'event',
                'placeholder' => null,
                'default_value' => '09:00',
                'help_text' => 'Format: 09:00 WIB',
                'validation_rules' => [
                    'required' => true,
                ],
                'display_order' => 8,
                'is_active' => true,
            ],
            [
                'template_id' => $sakeenah->id,
                'field_key' => 'akad_location',
                'field_label' => 'Lokasi Akad',
                'field_type' => 'textarea',
                'field_category' => 'venue',
                'placeholder' => 'contoh: Masjid Al-Ikhlas, Jl. Merdeka No. 123, Jakarta Selatan',
                'default_value' => null,
                'help_text' => 'Alamat lengkap tempat akad nikah',
                'validation_rules' => [
                    'required' => true,
                    'min' => 10,
                    'max' => 300,
                ],
                'display_order' => 9,
                'is_active' => true,
            ],

            // Reception Event Details
            [
                'template_id' => $sakeenah->id,
                'field_key' => 'reception_date',
                'field_label' => 'Tanggal Resepsi',
                'field_type' => 'date',
                'field_category' => 'event',
                'placeholder' => null,
                'default_value' => null,
                'help_text' => 'Pilih tanggal pelaksanaan resepsi',
                'validation_rules' => [
                    'required' => true,
                ],
                'display_order' => 10,
                'is_active' => true,
            ],
            [
                'template_id' => $sakeenah->id,
                'field_key' => 'reception_time',
                'field_label' => 'Waktu Resepsi',
                'field_type' => 'time',
                'field_category' => 'event',
                'placeholder' => null,
                'default_value' => '18:00',
                'help_text' => 'Format: 18:00 WIB',
                'validation_rules' => [
                    'required' => true,
                ],
                'display_order' => 11,
                'is_active' => true,
            ],
            [
                'template_id' => $sakeenah->id,
                'field_key' => 'reception_location',
                'field_label' => 'Lokasi Resepsi',
                'field_type' => 'textarea',
                'field_category' => 'venue',
                'placeholder' => 'contoh: Gedung Serbaguna Al-Falah, Jl. Kemang Raya No. 45, Jakarta Selatan',
                'default_value' => null,
                'help_text' => 'Alamat lengkap tempat resepsi',
                'validation_rules' => [
                    'required' => true,
                    'min' => 10,
                    'max' => 300,
                ],
                'display_order' => 12,
                'is_active' => true,
            ],

            // Additional Info
            [
                'template_id' => $sakeenah->id,
                'field_key' => 'gmaps_link',
                'field_label' => 'Link Google Maps',
                'field_type' => 'url',
                'field_category' => 'venue',
                'placeholder' => 'https://maps.google.com/...',
                'default_value' => null,
                'help_text' => 'Link Google Maps untuk lokasi acara (opsional)',
                'validation_rules' => [
                    'required' => false,
                ],
                'display_order' => 13,
                'is_active' => true,
            ],
            [
                'template_id' => $sakeenah->id,
                'field_key' => 'prewedding_photo',
                'field_label' => 'Foto Pre-Wedding',
                'field_type' => 'image',
                'field_category' => 'design',
                'placeholder' => null,
                'default_value' => null,
                'help_text' => 'Upload foto pre-wedding (maks 2MB, format: JPG, PNG)',
                'validation_rules' => [
                    'required' => false,
                ],
                'display_order' => 14,
                'is_active' => true,
            ],
            [
                'template_id' => $sakeenah->id,
                'field_key' => 'primary_color',
                'field_label' => 'Warna Utama',
                'field_type' => 'color',
                'field_category' => 'design',
                'placeholder' => null,
                'default_value' => '#8B7355',
                'help_text' => 'Pilih warna utama untuk tema undangan',
                'validation_rules' => [
                    'required' => false,
                ],
                'display_order' => 15,
                'is_active' => true,
            ],
        ];

        foreach ($fields as $field) {
            TemplateField::updateOrCreate(
                [
                    'template_id' => $field['template_id'],
                    'field_key' => $field['field_key'],
                ],
                $field
            );
        }

        // Mark template as having custom fields
        $sakeenah->update(['has_custom_fields' => true]);

        $this->command->info('✅ Template fields seeded successfully!');
        $this->command->info('   - Created ' . count($fields) . ' fields for Sakeenah template');
        $this->command->info('   - Categories: Couple (6), Event (4), Venue (3), Design (2)');
    }
}
