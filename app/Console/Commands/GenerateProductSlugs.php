<?php

namespace App\Console\Commands;

use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class GenerateProductSlugs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'products:generate-slugs';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate slugs for all products that don\'t have one';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Generating slugs for products...');

        $products = Product::whereNull('slug')->orWhere('slug', '')->get();

        if ($products->isEmpty()) {
            $this->info('All products already have slugs!');
            return Command::SUCCESS;
        }

        $bar = $this->output->createProgressBar($products->count());
        $bar->start();

        foreach ($products as $product) {
            $slug = Str::slug($product->name);
            $originalSlug = $slug;
            $counter = 1;

            // Ensure unique slug
            while (Product::where('slug', $slug)->where('id', '!=', $product->id)->exists()) {
                $slug = $originalSlug . '-' . $counter;
                $counter++;
            }

            $product->slug = $slug;
            $product->save();

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Successfully generated slugs for {$products->count()} products!");

        return Command::SUCCESS;
    }
}
