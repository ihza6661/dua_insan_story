<?php

namespace App\Console\Commands;

use App\Mail\ScheduledActivationReminder;
use App\Models\DigitalInvitation;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendScheduledActivationReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'invitations:send-reminders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send reminder emails for invitations scheduled to be activated within 24 hours';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Checking for invitations to remind...');

        // Get invitations scheduled to activate between 23 and 25 hours from now
        // This gives a 2-hour window for the daily job
        $now = Carbon::now();
        $reminderStart = $now->copy()->addHours(23);
        $reminderEnd = $now->copy()->addHours(25);

        $invitations = DigitalInvitation::query()
            ->where('status', 'draft')
            ->whereNotNull('scheduled_activation_at')
            ->whereBetween('scheduled_activation_at', [$reminderStart, $reminderEnd])
            ->with(['user', 'template'])
            ->get();

        if ($invitations->isEmpty()) {
            $this->info('No invitations to remind');
            return Command::SUCCESS;
        }

        $count = 0;
        foreach ($invitations as $invitation) {
            try {
                // Check if we've already sent a reminder (to avoid duplicates)
                // We'll check if the invitation has a 'reminder_sent_at' timestamp
                // If you want to track this, add a 'reminder_sent_at' column to the table
                // For now, we'll just send the reminder

                Mail::to($invitation->user->email)
                    ->send(new ScheduledActivationReminder($invitation, $invitation->user));

                $count++;
                $this->info("Sent reminder to {$invitation->user->email} for invitation #{$invitation->id}");
            } catch (\Exception $e) {
                $this->error("Failed to send reminder for invitation #{$invitation->id}: {$e->getMessage()}");
            }
        }

        $this->info("Sent {$count} reminder(s)");

        return Command::SUCCESS;
    }
}
