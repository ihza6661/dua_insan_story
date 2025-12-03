<?php

namespace App\Console\Commands;

use App\Models\AddOn;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CleanupDuplicateAddOns extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:cleanup-duplicate-add-ons {--dry-run : Run without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove duplicate add-ons from database, keeping the oldest entries';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $isDryRun = $this->option('dry-run');

        if ($isDryRun) {
            $this->info('🔍 DRY RUN MODE - No changes will be made');
        }

        $this->info('Starting duplicate add-ons cleanup...');

        // Define duplicate IDs to remove (newer duplicates)
        $duplicateIds = [67, 68, 69, 70, 71];

        // First, check if these IDs exist
        $existingDuplicates = AddOn::whereIn('id', $duplicateIds)->get();

        if ($existingDuplicates->isEmpty()) {
            $this->info('✅ No duplicate add-ons found. Database is clean!');
            return Command::SUCCESS;
        }

        $this->info(sprintf('Found %d duplicate add-ons to remove:', $existingDuplicates->count()));

        // Display what will be deleted
        $this->table(
            ['ID', 'Name', 'Price'],
            $existingDuplicates->map(fn($addOn) => [
                $addOn->id,
                $addOn->name,
                'Rp ' . number_format($addOn->price, 0, ',', '.')
            ])->toArray()
        );

        // Check for any references in product_add_ons
        $references = DB::table('product_add_ons')
            ->whereIn('add_on_id', $duplicateIds)
            ->count();

        if ($references > 0) {
            $this->error(sprintf('⚠️  WARNING: Found %d references to these add-ons in product_add_ons table!', $references));
            $this->error('Aborting to prevent data integrity issues.');
            return Command::FAILURE;
        }

        if ($isDryRun) {
            $this->info('✅ Dry run complete. Run without --dry-run to execute deletion.');
            return Command::SUCCESS;
        }

        // Confirm before deletion
        if (!$this->confirm('Proceed with deletion?', true)) {
            $this->info('Deletion cancelled.');
            return Command::SUCCESS;
        }

        // Perform deletion
        $deletedCount = AddOn::whereIn('id', $duplicateIds)->delete();

        $this->info(sprintf('✅ Successfully deleted %d duplicate add-ons', $deletedCount));

        // Show remaining add-ons
        $remaining = AddOn::orderBy('id')->get();
        $this->info(sprintf('Remaining add-ons in database: %d', $remaining->count()));

        $this->table(
            ['ID', 'Name', 'Price'],
            $remaining->map(fn($addOn) => [
                $addOn->id,
                $addOn->name,
                'Rp ' . number_format($addOn->price, 0, ',', '.')
            ])->toArray()
        );

        return Command::SUCCESS;
    }
}
