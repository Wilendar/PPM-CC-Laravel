<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewUserPendingApprovalMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public User $newUser) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '[PPM] Nowy uzytkownik czeka na zatwierdzenie',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.new-user-pending',
            with: ['newUser' => $this->newUser],
        );
    }
}
