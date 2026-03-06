<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BackupCompletedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $filename,
        public string $size,
        public float $durationSeconds,
    ) {
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if ($notifiable->hasNotificationEnabled('email_backup_completed')) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    /**
     * Get the array representation of the notification for database storage.
     *
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        $duration = round($this->durationSeconds, 1);

        return [
            'title' => 'Backup bazy danych ukonczony',
            'message' => "Backup \"{$this->filename}\" ({$this->size}) zostal ukonczony w {$duration}s.",
            'type' => 'backup_completed',
            'icon' => 'system',
            'action_url' => '/admin/backup',
        ];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $data = $this->toDatabase($notifiable);

        return (new MailMessage)
            ->subject($data['title'])
            ->view('emails.notification-branded', [
                'title' => $data['title'],
                'message' => $data['message'],
                'type' => $data['type'],
                'actionUrl' => url($data['action_url']),
                'actionText' => 'Zobacz w panelu',
            ]);
    }
}
