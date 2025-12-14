<?php

namespace App\Mail;

use App\Models\AbandonedCart;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AbandonedCartEmail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public AbandonedCart $abandonedCart,
        public string $reminderType // '1h', '24h', '3d'
    ) {
        //
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $subjects = [
            '1h' => 'Keranjang Anda Menunggu! ⏰',
            '24h' => 'Jangan Lewatkan Undangan Impian Anda! 💝',
            '3d' => 'Terakhir Kali! Dapatkan Diskon Khusus 10% 🎁',
        ];

        return new Envelope(
            subject: $subjects[$this->reminderType] ?? 'Keranjang Belanja Anda',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.abandoned-cart',
            with: [
                'abandonedCart' => $this->abandonedCart,
                'reminderType' => $this->reminderType,
                'recoveryUrl' => $this->abandonedCart->getRecoveryUrl(),
                'showDiscount' => $this->reminderType === '3d', // 10% discount on 3rd email
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
