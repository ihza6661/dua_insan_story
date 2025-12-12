<?php

namespace App\Jobs;

use App\Mail\DigitalInvitationReady;
use App\Models\Notification;
use App\Models\Order;
use App\Services\DigitalInvitationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ProcessDigitalInvitations implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public int $backoff = 60;

    /**
     * The order instance.
     */
    public Order $order;

    /**
     * Create a new job instance.
     */
    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    /**
     * Execute the job.
     */
    public function handle(DigitalInvitationService $digitalInvitationService): void
    {
        Log::info('ProcessDigitalInvitations job started', [
            'order_id' => $this->order->id,
            'job_id' => $this->job?->uuid(),
        ]);

        try {
            // Create invitations for ALL digital products in order (returns array)
            $invitations = $digitalInvitationService->createFromOrder($this->order);

            if (empty($invitations)) {
                Log::info('No digital invitations to process', [
                    'order_id' => $this->order->id,
                ]);

                return;
            }

            // Activate each invitation and send notifications
            foreach ($invitations as $invitation) {
                try {
                    $digitalInvitationService->activate($invitation->id);

                    Log::info('Digital invitation created and activated', [
                        'order_id' => $this->order->id,
                        'invitation_id' => $invitation->id,
                        'slug' => $invitation->slug,
                        'template' => $invitation->template->name ?? 'Unknown',
                    ]);

                    if ($this->order->customer) {
                        // Send email notification
                        $invitation->loadMissing('template');
                        Mail::to($this->order->customer->email)->send(
                            new DigitalInvitationReady($invitation, $this->order->customer)
                        );

                        // Create in-app notification
                        Notification::create([
                            'user_id' => $this->order->customer_id,
                            'type' => 'digital_invitation_ready',
                            'title' => 'Undangan Digital Anda Siap!',
                            'message' => "Undangan digital '{$invitation->template->name}' sudah siap digunakan. Klik untuk mulai kustomisasi.",
                            'data' => [
                                'invitation_id' => $invitation->id,
                                'slug' => $invitation->slug,
                                'template_name' => $invitation->template->name,
                                'action_url' => "/my-invitations/{$invitation->id}/edit",
                                'public_url' => "/undangan/{$invitation->slug}",
                            ],
                        ]);

                        Log::info('Digital invitation ready email and notification sent', [
                            'customer_email' => $this->order->customer->email,
                            'invitation_id' => $invitation->id,
                        ]);
                    }
                } catch (\Exception $e) {
                    // Log error for this specific invitation but continue processing others
                    Log::error('Failed to process single invitation in job', [
                        'order_id' => $this->order->id,
                        'invitation_id' => $invitation->id ?? 'unknown',
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);

                    // Continue to next invitation instead of failing entire job
                    continue;
                }
            }

            Log::info('ProcessDigitalInvitations job completed successfully', [
                'order_id' => $this->order->id,
                'invitations_count' => count($invitations),
                'job_id' => $this->job?->uuid(),
            ]);
        } catch (\Exception $e) {
            Log::error('ProcessDigitalInvitations job failed', [
                'order_id' => $this->order->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'job_id' => $this->job?->uuid(),
            ]);

            // Re-throw to trigger job retry mechanism
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('ProcessDigitalInvitations job failed after all retries', [
            'order_id' => $this->order->id,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
            'job_id' => $this->job?->uuid(),
        ]);

        // TODO: Consider sending admin notification or creating a failed job notification
        // for manual review and customer service follow-up
    }
}
