<?php

namespace App\Mail;

use App\Models\AdminNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AdminNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public AdminNotification $notification;

    /**
     * Create a new message instance.
     */
    public function __construct(AdminNotification $notification)
    {
        $this->notification = $notification;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $subject = $this->getSubjectPrefix() . $this->notification->title;
        
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
            view: 'emails.admin-notification',
            with: [
                'notification' => $this->notification,
                'dashboardUrl' => route('admin.dashboard'),
                'notificationUrl' => route('admin.notifications.show', $this->notification->id),
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

    /**
     * Get subject prefix based on priority and type
     */
    protected function getSubjectPrefix(): string
    {
        $prefix = '[PPM Admin] ';
        
        if ($this->notification->priority === AdminNotification::PRIORITY_CRITICAL) {
            $prefix .= '🚨 KRYTYCZNY: ';
        } elseif ($this->notification->priority === AdminNotification::PRIORITY_HIGH) {
            $prefix .= '⚠️ WYSOKI: ';
        } elseif ($this->notification->type === AdminNotification::TYPE_SECURITY) {
            $prefix .= '🛡️ BEZPIECZEŃSTWO: ';
        }
        
        return $prefix;
    }
}