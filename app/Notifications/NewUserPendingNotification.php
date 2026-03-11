<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewUserPendingNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public $newUser)
    {
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if ($notifiable->hasNotificationEnabled('email_new_user_pending')) {
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
        $userName = trim(($this->newUser->first_name ?? '') . ' ' . ($this->newUser->last_name ?? ''));
        $userName = $userName ?: $this->newUser->email;

        return [
            'title' => 'Nowy uzytkownik oczekuje na zatwierdzenie',
            'message' => "Uzytkownik {$userName} ({$this->newUser->email}) zarejestrował sie i oczekuje na zatwierdzenie konta.",
            'type' => 'new_user_pending',
            'icon' => 'security',
            'action_url' => '/admin/users',
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
