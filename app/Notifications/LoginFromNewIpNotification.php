<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LoginFromNewIpNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $ipAddress,
        public ?string $browser = null,
        public ?string $os = null,
        public ?string $city = null,
        public ?string $country = null,
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

        if ($notifiable->hasNotificationEnabled('email_login_new_ip')) {
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
        $details = [];

        if ($this->browser) {
            $details[] = $this->browser;
        }

        if ($this->os) {
            $details[] = $this->os;
        }

        $location = '';
        if ($this->city && $this->country) {
            $location = " z lokalizacji {$this->city}, {$this->country}";
        } elseif ($this->country) {
            $location = " z lokalizacji {$this->country}";
        }

        $browserInfo = $details ? ' (' . implode(', ', $details) . ')' : '';

        return [
            'title' => 'Logowanie z nowego adresu IP',
            'message' => "Wykryto logowanie z nowego adresu IP: {$this->ipAddress}{$browserInfo}{$location}.",
            'type' => 'login_new_ip',
            'icon' => 'security',
            'action_url' => '/profile/sessions',
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
