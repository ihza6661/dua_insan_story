<?php

namespace App\Console\Commands;

use App\Services\DigitalInvitationService;
use Illuminate\Console\Command;

class ActivateScheduledInvitations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'invitations:activate-scheduled';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Activate invitations that are scheduled for activation';

    /**
     * Execute the console command.
     */
    public function handle(DigitalInvitationService $service): int
    {
        $this->info('Checking for scheduled invitations...');

        $count = $service->activateScheduledInvitations();

        if ($count > 0) {
            $this->info("Activated {$count} invitation(s)");
        } else {
            $this->info('No invitations to activate');
        }

        return Command::SUCCESS;
    }
}
