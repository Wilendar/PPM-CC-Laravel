<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ImportProductPublishedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public $product,
        public ?int $publishedProductId = null,
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

        if ($notifiable->hasNotificationEnabled('email_import_published')) {
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
        $actionUrl = $this->publishedProductId
            ? "/products/{$this->publishedProductId}/edit"
            : '/products/import';

        return [
            'title' => 'Produkt opublikowany',
            'message' => "Produkt {$this->product->sku} ({$this->product->name}) zostal pomyslnie opublikowany.",
            'type' => 'import_published',
            'icon' => 'import',
            'action_url' => $actionUrl,
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
