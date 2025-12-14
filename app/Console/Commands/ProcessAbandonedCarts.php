<?php

namespace App\Console\Commands;

use App\Jobs\SendAbandonedCartEmail;
use App\Models\AbandonedCart;
use Illuminate\Console\Command;

class ProcessAbandonedCarts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'carts:process-abandoned
                            {--dry-run : Run without actually sending emails}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process abandoned carts and send reminder emails';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🛒 Processing abandoned carts...');
        $this->newLine();

        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->warn('⚠️  DRY RUN MODE - No emails will be sent');
            $this->newLine();
        }

        // Process first reminders (1 hour)
        $firstReminders = AbandonedCart::pendingFirstReminder()->get();
        $this->processReminders($firstReminders, '1h', 'First', $dryRun);

        // Process second reminders (24 hours)
        $secondReminders = AbandonedCart::pendingSecondReminder()->get();
        $this->processReminders($secondReminders, '24h', 'Second', $dryRun);

        // Process third reminders (3 days)
        $thirdReminders = AbandonedCart::pendingThirdReminder()->get();
        $this->processReminders($thirdReminders, '3d', 'Third', $dryRun);

        $this->newLine();
        $this->info('✅ Done!');

        return Command::SUCCESS;
    }

    /**
     * Process reminders for a specific type
     */
    protected function processReminders($carts, string $type, string $label, bool $dryRun): void
    {
        $count = $carts->count();

        if ($count === 0) {
            $this->line("  📭 {$label} reminders: No carts to process");
            return;
        }

        $this->info("  📧 {$label} reminders ({$type}): {$count} cart(s)");

        $bar = $this->output->createProgressBar($count);
        $bar->start();

        foreach ($carts as $cart) {
            if (!$dryRun) {
                SendAbandonedCartEmail::dispatch($cart, $type);
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        if ($dryRun) {
            $this->line("    ℹ️  Would send {$count} email(s)");
        } else {
            $this->line("    ✓ Queued {$count} email(s)");
        }

        $this->newLine();
    }
}
