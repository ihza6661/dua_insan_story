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

class DigitalInvitationReady extends Mailable implements ShouldQueue
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
            subject: '🎉 Your Digital Invitation is Ready!',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.digital-invitation-ready',
            with: [
                'invitation' => $this->invitation,
                'customer' => $this->customer,
                'template' => $this->invitation->template,
                'publicUrl' => config('app.frontend_url').'/undangan/'.$this->invitation->slug,
                'editUrl' => config('app.frontend_url').'/my-invitations',
                'expiresAt' => $this->invitation->expires_at,
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
