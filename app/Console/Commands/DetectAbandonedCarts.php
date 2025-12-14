<?php

namespace App\Console\Commands;

use App\Models\AbandonedCart;
use App\Models\Cart;
use Illuminate\Console\Command;

class DetectAbandonedCarts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'carts:detect-abandoned
                            {--threshold=1 : Hours since last activity to consider cart abandoned}
                            {--dry-run : Run without actually creating records}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Detect abandoned carts and create abandoned cart records';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔍 Detecting abandoned carts...');
        $this->newLine();

        $threshold = (int) $this->option('threshold');
        $dryRun = $this->option('dry-run');
        $cutoffTime = now()->subHours($threshold);

        if ($dryRun) {
            $this->warn('⚠️  DRY RUN MODE - No records will be created');
            $this->newLine();
        }

        $this->line("  📅 Cutoff time: {$cutoffTime->format('Y-m-d H:i:s')}");
        $this->line("  ⏱️  Threshold: {$threshold} hour(s)");
        $this->newLine();

        // Find carts that haven't been updated for X hours and have authenticated users
        $abandonedCarts = Cart::with(['user', 'items.product'])
            ->where('updated_at', '<=', $cutoffTime)
            ->whereNotNull('user_id') // Only logged-in users (they have emails)
            ->whereHas('items') // Only carts with items
            ->get();

        $this->info("  📊 Found {$abandonedCarts->count()} abandoned cart(s)");
        $this->newLine();

        if ($abandonedCarts->isEmpty()) {
            $this->info('✅ No abandoned carts to process');
            return Command::SUCCESS;
        }

        $created = 0;
        $skipped = 0;

        $bar = $this->output->createProgressBar($abandonedCarts->count());
        $bar->start();

        foreach ($abandonedCarts as $cart) {
            // Skip if user doesn't exist
            if (!$cart->user) {
                $skipped++;
                $bar->advance();
                continue;
            }

            // Skip if already tracked
            $existing = AbandonedCart::where('session_id', $cart->session_id)
                ->orWhere(function ($query) use ($cart) {
                    $query->where('user_id', $cart->user_id)
                          ->where('abandoned_at', '>=', now()->subDays(7));
                })
                ->where('is_recovered', false)
                ->first();

            if ($existing) {
                $skipped++;
                $bar->advance();
                continue;
            }

            // Prepare cart items
            $cartItems = $cart->items->map(function ($item) {
                return [
                    'product_id' => $item->product_id,
                    'name' => $item->product->name,
                    'quantity' => $item->quantity,
                    'price' => $item->price,
                    'image' => $item->product->featured_image?->image_url,
                ];
            })->toArray();

            // Calculate total
            $total = $cart->items->sum(function ($item) {
                return $item->price * $item->quantity;
            });

            if (!$dryRun) {
                AbandonedCart::create([
                    'user_id' => $cart->user_id,
                    'session_id' => $cart->session_id,
                    'email' => $cart->user->email,
                    'name' => $cart->user->name,
                    'cart_items' => $cartItems,
                    'cart_total' => $total,
                    'items_count' => $cart->items->count(),
                    'abandoned_at' => $cart->updated_at,
                ]);

                $created++;
            } else {
                $created++; // Count what would be created
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        if ($dryRun) {
            $this->line("  ℹ️  Would create {$created} record(s)");
            $this->line("  ℹ️  Would skip {$skipped} record(s) (already tracked)");
        } else {
            $this->line("  ✓ Created {$created} record(s)");
            $this->line("  ⏭  Skipped {$skipped} record(s) (already tracked)");
        }

        $this->newLine();
        $this->info('✅ Done!');

        return Command::SUCCESS;
    }
}
