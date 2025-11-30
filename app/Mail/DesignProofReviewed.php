<?php

namespace App\Mail;

use App\Models\DesignProof;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DesignProofReviewed extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public DesignProof $designProof
    ) {
        //
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $subject = match ($this->designProof->status) {
            'approved' => 'Design Proof Approved - Ready to Proceed',
            'revision_requested' => 'Design Revision Requested',
            'rejected' => 'Design Proof Rejected',
            default => 'Design Proof Status Update',
        };

        return new Envelope(
            subject: $subject,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.design-proof-reviewed',
            with: [
                'designProof' => $this->designProof,
                'orderItem' => $this->designProof->orderItem,
                'order' => $this->designProof->orderItem->order,
                'product' => $this->designProof->orderItem->product,
                'status' => $this->designProof->status,
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
