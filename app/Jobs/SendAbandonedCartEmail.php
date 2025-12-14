<?php

namespace App\Jobs;

use App\Mail\AbandonedCartEmail;
use App\Models\AbandonedCart;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendAbandonedCartEmail implements ShouldQueue
{
    use Queueable;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     *
     * @var int
     */
    public $backoff = 60;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public AbandonedCart $abandonedCart,
        public string $reminderType // '1h', '24h', '3d'
    ) {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Check if cart is already recovered
        if ($this->abandonedCart->is_recovered) {
            Log::info('Abandoned cart already recovered', [
                'cart_id' => $this->abandonedCart->id,
            ]);
            return;
        }

        try {
            // Send email
            Mail::to($this->abandonedCart->email)
                ->send(new AbandonedCartEmail($this->abandonedCart, $this->reminderType));

            // Update sent timestamp
            $this->updateSentTimestamp();

            Log::info('Abandoned cart email sent successfully', [
                'cart_id' => $this->abandonedCart->id,
                'email' => $this->abandonedCart->email,
                'reminder_type' => $this->reminderType,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send abandoned cart email', [
                'cart_id' => $this->abandonedCart->id,
                'email' => $this->abandonedCart->email,
                'reminder_type' => $this->reminderType,
                'error' => $e->getMessage(),
            ]);

            throw $e; // Re-throw to trigger job retry
        }
    }

    /**
     * Update the sent timestamp based on reminder type
     */
    protected function updateSentTimestamp(): void
    {
        match ($this->reminderType) {
            '1h' => $this->abandonedCart->update(['first_reminder_sent_at' => now()]),
            '24h' => $this->abandonedCart->update(['second_reminder_sent_at' => now()]),
            '3d' => $this->abandonedCart->update(['third_reminder_sent_at' => now()]),
            default => null,
        };
    }
}
