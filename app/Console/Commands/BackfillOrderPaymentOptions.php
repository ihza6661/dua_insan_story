<?php

namespace App\Console\Commands;

use App\Models\Order;
use Illuminate\Console\Command;

class BackfillOrderPaymentOptions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'orders:backfill-payment-options {--dry-run : Run without making changes} {--include-orphaned : Handle orders without payment records}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Backfill payment_option field for existing orders from their payment records';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $includeOrphaned = $this->option('include-orphaned');

        if ($dryRun) {
            $this->info('Running in DRY RUN mode - no changes will be made');
        }

        $this->info('Finding orders with missing payment_option...');

        // Get orders without payment_option that have at least one payment
        $orders = Order::whereNull('payment_option')
            ->has('payments')
            ->with('payments')
            ->get();

        if ($orders->isEmpty()) {
            $this->info('No orders with payment records found that need backfilling.');
        } else {
            $this->info("Found {$orders->count()} orders with payment records to backfill");

            $bar = $this->output->createProgressBar($orders->count());
            $bar->start();

            $updated = 0;
            $skipped = 0;

            foreach ($orders as $order) {
                // Get the first payment for this order
                $firstPayment = $order->payments()
                    ->orderBy('created_at', 'asc')
                    ->first();

                if (! $firstPayment || ! $firstPayment->payment_type) {
                    $skipped++;
                    $bar->advance();

                    continue;
                }

                // Map payment_type to payment_option
                $paymentOption = $firstPayment->payment_type;

                if (! $dryRun) {
                    $order->update(['payment_option' => $paymentOption]);
                }

                $updated++;
                $bar->advance();
            }

            $bar->finish();
            $this->newLine(2);

            if ($dryRun) {
                $this->info("DRY RUN: Would update {$updated} orders");
            } else {
                $this->info("Successfully updated {$updated} orders");
            }

            if ($skipped > 0) {
                $this->warn("Skipped {$skipped} orders (no valid payment_type found)");
            }
        }

        // Handle orphaned orders (paid orders without payment records)
        if ($includeOrphaned) {
            $this->newLine();
            $this->info('Checking for orphaned orders (paid orders without payment records)...');

            $orphanedOrders = Order::whereNull('payment_option')
                ->where('payment_status', Order::PAYMENT_STATUS_PAID)
                ->whereDoesntHave('payments')
                ->get();

            if ($orphanedOrders->isEmpty()) {
                $this->info('No orphaned orders found.');
            } else {
                $this->warn("Found {$orphanedOrders->count()} orphaned order(s):");
                
                foreach ($orphanedOrders as $order) {
                    $this->line("  - {$order->order_number} (Status: {$order->payment_status})");
                }

                $this->newLine();
                $this->comment('These orders are marked as paid but have no payment records.');
                $this->comment('Setting payment_option to "full" as default...');

                $orphanedUpdated = 0;
                foreach ($orphanedOrders as $order) {
                    if (! $dryRun) {
                        $order->update(['payment_option' => 'full']);
                    }
                    $orphanedUpdated++;
                }

                if ($dryRun) {
                    $this->info("DRY RUN: Would set payment_option='full' for {$orphanedUpdated} orphaned orders");
                } else {
                    $this->info("Set payment_option='full' for {$orphanedUpdated} orphaned orders");
                }

                $this->newLine();
                $this->warn('⚠️  RECOMMENDATION: Run fix-orphaned-orders.php to create proper payment records');
            }
        }

        return Command::SUCCESS;
    }
}
