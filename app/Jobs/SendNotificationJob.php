<?php

namespace App\Jobs;

use App\Models\AdminNotification;
use App\Mail\AdminNotificationMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class SendNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public AdminNotification $notification;

    /**
     * Create a new job instance.
     */
    public function __construct(AdminNotification $notification)
    {
        $this->notification = $notification;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if (!$this->notification->shouldSendEmail()) {
            return;
        }

        $recipients = $this->notification->recipients ?? [];
        
        if (empty($recipients)) {
            Log::warning('No recipients for notification email', [
                'notification_id' => $this->notification->id,
            ]);
            return;
        }

        foreach ($recipients as $email) {
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                Log::warning('Invalid email address in notification recipients', [
                    'notification_id' => $this->notification->id,
                    'email' => $email,
                ]);
                continue;
            }

            try {
                Mail::to($email)->send(new AdminNotificationMail($this->notification));
                
                Log::info('Notification email sent', [
                    'notification_id' => $this->notification->id,
                    'email' => $email,
                    'type' => $this->notification->type,
                    'priority' => $this->notification->priority,
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to send notification email', [
                    'notification_id' => $this->notification->id,
                    'email' => $email,
                    'error' => $e->getMessage(),
                ]);
                
                // Re-throw to allow retry mechanism
                throw $e;
            }
        }

        // Mark as sent
        $this->notification->update(['sent_at' => now()]);
    }

    /**
     * Get the tags that should be assigned to the job.
     *
     * @return array<int, string>
     */
    public function tags(): array
    {
        return [
            'notification',
            'email',
            'notification:' . $this->notification->id,
            'type:' . $this->notification->type,
            'priority:' . $this->notification->priority,
        ];
    }
}