<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SyncFailedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $jobName,
        public string $errorMessage,
        public ?int $shopId = null,
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

        if ($notifiable->hasNotificationEnabled('email_sync_failed')) {
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
        return [
            'title' => 'Synchronizacja nieudana',
            'message' => "Zadanie \"{$this->jobName}\" zakonczylo sie bledem: {$this->errorMessage}",
            'type' => 'sync_failed',
            'icon' => 'sync',
            'action_url' => '/admin/shops',
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
                'notificationMessage' => $data['message'],
                'type' => $data['type'],
                'actionUrl' => url($data['action_url']),
                'actionText' => 'Zobacz w panelu',
            ]);
    }
}
