<?php

namespace App\Console\Commands;

use App\Models\Attribute;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CleanupDuplicateAttributes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:cleanup-duplicate-attributes {--dry-run : Run without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove duplicate attributes and their values, keeping only the ones in use';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $isDryRun = $this->option('dry-run');

        if ($isDryRun) {
            $this->info('🔍 DRY RUN MODE - No changes will be made');
        }

        $this->info('Starting duplicate attributes cleanup...');

        // Find attributes that are actually being used
        $usedAttributeIds = DB::table('attribute_values')
            ->join('product_variant_options', 'attribute_values.id', '=', 'product_variant_options.attribute_value_id')
            ->distinct()
            ->pluck('attribute_values.attribute_id')
            ->toArray();

        $this->info(sprintf('Found %d attributes currently in use: %s', 
            count($usedAttributeIds), 
            implode(', ', $usedAttributeIds)
        ));

        // Find all attributes
        $allAttributes = Attribute::with('attributeValues')->orderBy('id')->get();
        $this->info(sprintf('Total attributes in database: %d', $allAttributes->count()));

        // Separate used from unused
        $unusedAttributes = $allAttributes->whereNotIn('id', $usedAttributeIds);

        if ($unusedAttributes->isEmpty()) {
            $this->info('✅ No duplicate/unused attributes found. Database is clean!');
            return Command::SUCCESS;
        }

        $this->info(sprintf('Found %d unused/duplicate attributes to remove:', $unusedAttributes->count()));

        // Display what will be deleted
        $tableData = $unusedAttributes->map(function ($attr) {
            return [
                $attr->id,
                $attr->name,
                $attr->attributeValues->count() . ' values',
                $attr->created_at->format('Y-m-d'),
            ];
        })->toArray();

        $this->table(
            ['ID', 'Name', 'Values', 'Created'],
            $tableData
        );

        // Count total attribute values that will be deleted
        $totalValuesToDelete = $unusedAttributes->sum(fn($attr) => $attr->attributeValues->count());
        $this->info(sprintf('This will also delete %d associated attribute values', $totalValuesToDelete));

        if ($isDryRun) {
            $this->info('✅ Dry run complete. Run without --dry-run to execute deletion.');
            return Command::SUCCESS;
        }

        // Confirm before deletion
        if (!$this->confirm('Proceed with deletion?', true)) {
            $this->info('Deletion cancelled.');
            return Command::SUCCESS;
        }

        // Perform deletion (cascade will delete attribute_values automatically)
        $idsToDelete = $unusedAttributes->pluck('id')->toArray();
        $deletedCount = Attribute::whereIn('id', $idsToDelete)->delete();

        $this->info(sprintf('✅ Successfully deleted %d duplicate attributes', $deletedCount));

        // Show remaining attributes
        $remaining = Attribute::with('attributeValues')->orderBy('id')->get();
        $this->info(sprintf('Remaining attributes in database: %d', $remaining->count()));

        $this->table(
            ['ID', 'Name', 'Values Count'],
            $remaining->map(fn($attr) => [
                $attr->id,
                $attr->name,
                $attr->attributeValues->count(),
            ])->toArray()
        );

        return Command::SUCCESS;
    }
}
