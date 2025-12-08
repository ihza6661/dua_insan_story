<?php

namespace Database\Seeders;

use App\Models\InvitationTemplate;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DigitalProductSeeder extends Seeder
{
    /**
     * Seed digital products for invitation templates.
     */
    public function run(): void
    {
        $this->command->info('🚀 Creating digital products for invitation templates...');
        $this->command->info('');

        // Get or create digital category
        $category = ProductCategory::firstOrCreate(
            ['name' => 'Undangan Digital'],
            ['description' => 'Digital invitation templates that can be customized and shared online']
        );

        // Get all active templates
        $templates = InvitationTemplate::where('is_active', true)->get();

        if ($templates->isEmpty()) {
            $this->command->warn('⚠️  No invitation templates found. Please run InvitationTemplateSeeder first.');
            return;
        }

        $created = 0;
        $skipped = 0;

        foreach ($templates as $template) {
            // Check if product already exists for this template
            $existingProduct = Product::where('template_id', $template->id)
                ->where('product_type', 'digital')
                ->first();

            if ($existingProduct) {
                $this->command->warn("   ⏭️  Skipped: Product already exists for '{$template->name}'");
                $skipped++;
                continue;
            }

            // Create product
            $product = Product::create([
                'category_id' => $category->id,
                'product_type' => 'digital',
                'template_id' => $template->id,
                'name' => "Undangan Digital - {$template->name}",
                'slug' => Str::slug("undangan-digital-{$template->name}"),
                'description' => $template->description . "\n\n" .
                    "✨ Undangan digital ini dapat dikustomisasi dengan data Anda dan dibagikan melalui link.\n" .
                    "🌿 Tidak perlu cetak, ramah lingkungan, dan hemat biaya!\n" .
                    "📱 Bagikan via WhatsApp, Instagram, atau platform lainnya.\n" .
                    "⚡ Aktif selama 12 bulan sejak diaktifkan.\n" .
                    "📸 Upload hingga 5 foto.\n" .
                    "🗺️ Integrasi Google Maps untuk lokasi acara.",
                'base_price' => $template->price,
                'weight' => 0, // Digital products have no weight
                'min_order_quantity' => 1,
                'is_active' => true,
            ]);

            // Create a default variant for digital product (required for images)
            // Digital products don't need variants but the image system requires it
            $variant = ProductVariant::create([
                'product_id' => $product->id,
                'price' => (int) $template->price, // Price in Rupiah
                'weight' => 0, // Digital = no weight
                'stock' => 9999, // High stock (unlimited digital)
            ]);

            // Create product image (use template thumbnail)
            ProductImage::create([
                'product_id' => $product->id,
                'product_variant_id' => $variant->id,
                'image' => $template->thumbnail_image,
                'alt_text' => $product->name,
                'is_featured' => true,
            ]);

            $this->command->info("   ✅ Created: {$product->name}");
            $this->command->info("      ├─ Product ID: {$product->id} | Slug: {$product->slug}");
            $this->command->info("      ├─ Template ID: {$template->id}");
            $this->command->info("      ├─ Variant ID: {$variant->id} | Price: Rp " . number_format($variant->price, 0, ',', '.'));
            $this->command->info("      └─ Image: {$template->thumbnail_image}");
            $created++;
        }

        $this->command->info('');
        $this->command->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->command->info('📊 Summary:');
        $this->command->info("   ✅ Products created: {$created}");
        $this->command->info("   ⏭️  Products skipped: {$skipped}");
        $this->command->info('   📦 Total digital products: ' . Product::where('product_type', 'digital')->count());
        $this->command->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->command->info('');
        $this->command->info('🎉 Digital products seeding completed successfully!');
    }
}
