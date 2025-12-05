<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('slug')->nullable()->after('name');
        });
        
        // Generate slugs for existing products
        $this->generateSlugsForExistingProducts();
        
        // Now make slug required and unique
        Schema::table('products', function (Blueprint $table) {
            $table->string('slug')->nullable(false)->unique()->change();
        });
    }
    
    /**
     * Generate slugs for all existing products
     */
    private function generateSlugsForExistingProducts(): void
    {
        $products = \App\Models\Product::whereNull('slug')->orWhere('slug', '')->get();
        
        foreach ($products as $product) {
            $slug = \Illuminate\Support\Str::slug($product->name);
            $originalSlug = $slug;
            $counter = 1;
            
            // Ensure unique slug
            while (\App\Models\Product::where('slug', $slug)->where('id', '!=', $product->id)->exists()) {
                $slug = $originalSlug . '-' . $counter;
                $counter++;
            }
            
            $product->slug = $slug;
            $product->save();
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('slug');
        });
    }
};
