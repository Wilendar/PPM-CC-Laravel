<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ImportProductScheduledNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public $product,
        public string $scheduledDate,
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

        if ($notifiable->hasNotificationEnabled('email_import_scheduled')) {
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
            'title' => 'Data publikacji ustawiona',
            'message' => "Produkt {$this->product->sku} zostal zaplanowany do publikacji na {$this->scheduledDate}.",
            'type' => 'import_scheduled',
            'icon' => 'import',
            'action_url' => '/products/import',
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
