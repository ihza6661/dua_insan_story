<?php

namespace App\Mail;

use App\Models\DigitalInvitation;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ScheduledActivationReminder extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public DigitalInvitation $invitation,
        public User $customer
    ) {
        //
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '⏰ Reminder: Your Digital Invitation Will Be Activated Tomorrow',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.scheduled-activation-reminder',
            with: [
                'invitation' => $this->invitation,
                'customer' => $this->customer,
                'template' => $this->invitation->template,
                'scheduledAt' => $this->invitation->scheduled_activation_at,
                'editUrl' => config('app.frontend_url').'/my-invitations',
                'previewUrl' => config('app.frontend_url').'/my-invitations?preview='.$this->invitation->id,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
