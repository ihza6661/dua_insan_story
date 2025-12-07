<?php

namespace Database\Seeders;

use App\Models\InvitationTemplate;
use Illuminate\Database\Seeder;

class InvitationTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $templates = [
            [
                'name' => 'Sakeenah - Islamic Modern',
                'slug' => 'sakeenah-islamic-modern',
                'description' => 'Beautiful modern Islamic wedding invitation with elegant animations and soft romantic colors. Perfect for Indonesian Muslim weddings with a contemporary touch.',
                'thumbnail_image' => '/media/templates/sakeenah-thumb.jpg',
                'price' => 150000.00,
                'template_component' => 'SakenahTemplate',
                'is_active' => true,
                'usage_count' => 0,
            ],
            [
                'name' => 'Classic Elegant - Traditional',
                'slug' => 'classic-elegant-traditional',
                'description' => 'Timeless classic wedding invitation with gold and white tones. Clean, minimalist design with floral accents perfect for traditional Indonesian weddings.',
                'thumbnail_image' => '/media/templates/classic-elegant-thumb.jpg',
                'price' => 150000.00,
                'template_component' => 'ClassicElegantTemplate',
                'is_active' => true,
                'usage_count' => 0,
            ],
        ];

        foreach ($templates as $template) {
            InvitationTemplate::updateOrCreate(
                ['slug' => $template['slug']],
                $template
            );
        }

        $this->command->info('✅ Invitation templates seeded successfully!');
        $this->command->info('   - Sakeenah - Islamic Modern');
        $this->command->info('   - Classic Elegant - Traditional');
    }
}
