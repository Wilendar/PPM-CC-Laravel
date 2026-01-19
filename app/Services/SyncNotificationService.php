<?php

namespace App\Services;

use App\Models\SyncJob;
use App\Models\SystemSetting;
use App\Services\Channels\TeamsChannel;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Sync Notification Service - 2.2.1.2.3
 *
 * Centralized service for sending sync job notifications.
 * Supports multiple channels: Email, Teams.
 *
 * Features:
 * - Configurable notification events (success, failure, retry_exhausted)
 * - Multiple notification channels
 * - Rate limiting to prevent notification spam
 * - Integration with SystemSettings for configuration
 */
class SyncNotificationService
{
    /**
     * Notification cooldown in seconds to prevent spam.
     */
    private const NOTIFICATION_COOLDOWN = 60;

    public function __construct(
        private TeamsChannel $teamsChannel
    ) {
    }

    /**
     * Send notification for a sync job event.
     *
     * @param SyncJob $syncJob The sync job to notify about
     * @param string $event Event type: 'success', 'failure', 'retry_exhausted'
     * @return void
     */
    public function sendSyncNotification(SyncJob $syncJob, string $event): void
    {
        try {
            $settings = $this->getNotificationSettings();

            // Check if notifications are enabled
            if (!$settings['enabled']) {
                Log::debug('Sync notifications are disabled');
                return;
            }

            // Check if this event type should trigger notification
            if (!$this->shouldNotify($settings, $event)) {
                Log::debug('Notification not configured for this event', [
                    'event' => $event,
                    'sync_job_id' => $syncJob->id,
                ]);
                return;
            }

            // Check cooldown to prevent notification spam
            if (!$this->checkCooldown($syncJob)) {
                Log::debug('Notification skipped due to cooldown', [
                    'sync_job_id' => $syncJob->id,
                ]);
                return;
            }

            $data = $this->prepareSyncData($syncJob, $event);

            // Send to Email channel
            if (in_array('email', $settings['channels']) && !empty($settings['email_recipients'])) {
                $this->sendEmailNotifications($data, $settings['email_recipients']);
            }

            // Send to Teams channel
            if ($settings['teams_enabled'] && !empty($settings['teams_webhook_url'])) {
                $this->sendTeamsNotification($data, $settings['teams_webhook_url']);
            }

            // Update last notification timestamp
            $syncJob->update(['last_notification_sent' => now()]);

            Log::info('Sync notification sent', [
                'sync_job_id' => $syncJob->id,
                'event' => $event,
                'channels' => $settings['channels'],
                'teams_enabled' => $settings['teams_enabled'],
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send sync notification', [
                'sync_job_id' => $syncJob->id ?? null,
                'event' => $event,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get notification settings from SystemSettings.
     *
     * @return array Notification configuration
     */
    private function getNotificationSettings(): array
    {
        return [
            'enabled' => (bool) SystemSetting::get('sync.notifications.enabled', true),
            'on_success' => (bool) SystemSetting::get('sync.notifications.on_success', false),
            'on_failure' => (bool) SystemSetting::get('sync.notifications.on_failure', true),
            'on_retry_exhausted' => (bool) SystemSetting::get('sync.notifications.on_retry_exhausted', true),
            'channels' => SystemSetting::get('sync.notifications.channels', ['email']),
            'email_recipients' => SystemSetting::get('sync.notifications.email_recipients', []),
            'teams_enabled' => (bool) SystemSetting::get('sync.notifications.teams_enabled', false),
            'teams_webhook_url' => SystemSetting::get('sync.notifications.teams_webhook_url', ''),
        ];
    }

    /**
     * Check if notification should be sent for given event.
     *
     * @param array $settings Notification settings
     * @param string $event Event type
     * @return bool
     */
    private function shouldNotify(array $settings, string $event): bool
    {
        return match ($event) {
            'success' => $settings['on_success'],
            'failure' => $settings['on_failure'],
            'retry_exhausted' => $settings['on_retry_exhausted'],
            default => false,
        };
    }

    /**
     * Check notification cooldown to prevent spam.
     *
     * @param SyncJob $syncJob
     * @return bool True if cooldown has passed
     */
    private function checkCooldown(SyncJob $syncJob): bool
    {
        if (!$syncJob->last_notification_sent) {
            return true;
        }

        $secondsSinceLastNotification = now()->diffInSeconds($syncJob->last_notification_sent);
        return $secondsSinceLastNotification >= self::NOTIFICATION_COOLDOWN;
    }

    /**
     * Prepare notification data from SyncJob.
     *
     * @param SyncJob $syncJob
     * @param string $event
     * @return array
     */
    private function prepareSyncData(SyncJob $syncJob, string $event): array
    {
        $shopName = 'Unknown';

        // Try to get shop name from relation
        if ($syncJob->target_type === 'prestashop_shop' && $syncJob->prestashopShop) {
            $shopName = $syncJob->prestashopShop->name;
        } elseif ($syncJob->parameters && isset($syncJob->parameters['shop_name'])) {
            $shopName = $syncJob->parameters['shop_name'];
        }

        // Calculate duration
        $duration = 'N/A';
        if ($syncJob->started_at && $syncJob->completed_at) {
            $durationSeconds = $syncJob->started_at->diffInSeconds($syncJob->completed_at);
            $duration = "{$durationSeconds}s";
        }

        // Get result summary
        $productsCount = 0;
        if ($syncJob->result_summary) {
            $productsCount = $syncJob->result_summary['synced']
                ?? $syncJob->result_summary['total']
                ?? $syncJob->result_summary['products_imported']
                ?? 0;
        }

        return [
            'job_name' => $syncJob->job_name ?? 'Sync Job',
            'job_type' => $syncJob->job_type ?? 'unknown',
            'shop_name' => $shopName,
            'status' => $event,
            'duration' => $duration,
            'products_count' => $productsCount,
            'message' => $event === 'failure' ? ($syncJob->error_message ?? 'Unknown error') : '',
            'job_id' => $syncJob->job_id,
            'sync_job_id' => $syncJob->id,
            'timestamp' => now()->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * Send email notifications to recipients.
     *
     * @param array $data Notification data
     * @param array $recipients Email addresses
     * @return void
     */
    private function sendEmailNotifications(array $data, array $recipients): void
    {
        foreach ($recipients as $email) {
            try {
                // Use Laravel's Mail facade for simple notification
                Mail::raw(
                    $this->formatEmailBody($data),
                    function ($message) use ($email, $data) {
                        $message->to($email)
                            ->subject($this->formatEmailSubject($data));
                    }
                );

                Log::debug('Email notification sent', ['email' => $email]);

            } catch (\Exception $e) {
                Log::warning('Failed to send email notification', [
                    'email' => $email,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Send Teams notification.
     *
     * @param array $data Notification data
     * @param string $webhookUrl Teams webhook URL
     * @return void
     */
    private function sendTeamsNotification(array $data, string $webhookUrl): void
    {
        try {
            $result = $this->teamsChannel->sendSyncNotification($webhookUrl, $data);

            if ($result) {
                Log::debug('Teams notification sent successfully');
            } else {
                Log::warning('Teams notification failed to send');
            }

        } catch (\Exception $e) {
            Log::warning('Failed to send Teams notification', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Format email subject line.
     *
     * @param array $data
     * @return string
     */
    private function formatEmailSubject(array $data): string
    {
        $statusIcon = match ($data['status']) {
            'success' => '[OK]',
            'failure' => '[BLAD]',
            'retry_exhausted' => '[UWAGA]',
            default => '[INFO]',
        };

        return "{$statusIcon} PPM Sync: {$data['job_name']} - {$data['shop_name']}";
    }

    /**
     * Format email body text.
     *
     * @param array $data
     * @return string
     */
    private function formatEmailBody(array $data): string
    {
        $lines = [
            "PPM Sync Notification",
            "=====================",
            "",
            "Job: {$data['job_name']}",
            "Shop: {$data['shop_name']}",
            "Status: " . ucfirst($data['status']),
            "Duration: {$data['duration']}",
            "Products: {$data['products_count']}",
            "Time: {$data['timestamp']}",
        ];

        if (!empty($data['message'])) {
            $lines[] = "";
            $lines[] = "Error: {$data['message']}";
        }

        $lines[] = "";
        $lines[] = "---";
        $lines[] = "PPM - Prestashop Product Manager";
        $lines[] = config('app.url', 'https://ppm.mpptrade.pl');

        return implode("\n", $lines);
    }
}
