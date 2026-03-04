<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class UserRejectedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public ?string $reason = null
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '[PPM] Informacja o koncie',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.user-rejected',
            with: [
                'user' => $this->user,
                'reason' => $this->reason,
            ],
        );
    }
}
