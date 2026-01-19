<?php

namespace App\Services;

use App\Models\BugReport;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\Channels\TeamsChannel;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Bug Report Notification Service
 *
 * Centralized service for sending bug report notifications.
 * Supports multiple channels: Email, Teams.
 *
 * Features:
 * - Notify admins about new reports
 * - Notify reporters about status changes
 * - Teams integration with Adaptive Cards
 */
class BugReportNotificationService
{
    public function __construct(
        private TeamsChannel $teamsChannel
    ) {
    }

    /**
     * Notify admins about a new bug report.
     *
     * @param BugReport $report
     * @return void
     */
    public function notifyNewReport(BugReport $report): void
    {
        try {
            $settings = $this->getNotificationSettings();

            if (!$settings['enabled']) {
                return;
            }

            $data = $this->prepareNewReportData($report);

            // Email notifications to admins
            if ($settings['email_enabled'] && !empty($settings['admin_emails'])) {
                $this->sendAdminEmailNotification($data, $settings['admin_emails']);
            }

            // Teams notification
            if ($settings['teams_enabled'] && !empty($settings['teams_webhook_url'])) {
                $this->sendTeamsNotification($data, $settings['teams_webhook_url']);
            }

            Log::info('Bug report notification sent to admins', [
                'report_id' => $report->id,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send bug report notification', [
                'report_id' => $report->id ?? null,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Notify reporter about status change.
     *
     * @param BugReport $report
     * @param string $oldStatus
     * @return void
     */
    public function notifyStatusChanged(BugReport $report, string $oldStatus): void
    {
        try {
            $settings = $this->getNotificationSettings();

            if (!$settings['enabled'] || !$settings['notify_reporter_on_status_change']) {
                return;
            }

            $reporter = $report->reporter;
            if (!$reporter || !$reporter->email) {
                return;
            }

            $data = $this->prepareStatusChangeData($report, $oldStatus);

            // Send email to reporter
            $this->sendReporterEmailNotification($data, $reporter->email);

            Log::info('Status change notification sent to reporter', [
                'report_id' => $report->id,
                'reporter_email' => $reporter->email,
                'old_status' => $oldStatus,
                'new_status' => $report->status,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send status change notification', [
                'report_id' => $report->id ?? null,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Notify reporter when report is resolved.
     *
     * @param BugReport $report
     * @return void
     */
    public function notifyResolved(BugReport $report): void
    {
        try {
            $settings = $this->getNotificationSettings();

            if (!$settings['enabled'] || !$settings['notify_reporter_on_resolution']) {
                return;
            }

            $reporter = $report->reporter;
            if (!$reporter || !$reporter->email) {
                return;
            }

            $data = $this->prepareResolutionData($report);

            // Send email to reporter
            $this->sendResolutionEmailNotification($data, $reporter->email);

            // Teams notification for resolved reports
            if ($settings['teams_enabled'] && !empty($settings['teams_webhook_url'])) {
                $this->sendTeamsResolutionNotification($data, $settings['teams_webhook_url']);
            }

            Log::info('Resolution notification sent', [
                'report_id' => $report->id,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send resolution notification', [
                'report_id' => $report->id ?? null,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get notification settings.
     *
     * @return array
     */
    private function getNotificationSettings(): array
    {
        return [
            'enabled' => (bool) SystemSetting::get('bug_reports.notifications.enabled', true),
            'email_enabled' => (bool) SystemSetting::get('bug_reports.notifications.email_enabled', true),
            'teams_enabled' => (bool) SystemSetting::get('bug_reports.notifications.teams_enabled', false),
            'teams_webhook_url' => SystemSetting::get('bug_reports.notifications.teams_webhook_url', ''),
            'admin_emails' => SystemSetting::get('bug_reports.notifications.admin_emails', $this->getDefaultAdminEmails()),
            'notify_reporter_on_status_change' => (bool) SystemSetting::get('bug_reports.notifications.notify_reporter_on_status_change', true),
            'notify_reporter_on_resolution' => (bool) SystemSetting::get('bug_reports.notifications.notify_reporter_on_resolution', true),
        ];
    }

    /**
     * Get default admin emails from users with Admin/Manager role.
     *
     * @return array
     */
    private function getDefaultAdminEmails(): array
    {
        return User::whereHas('roles', function ($q) {
            $q->whereIn('name', ['Admin', 'Manager']);
        })->pluck('email')->toArray();
    }

    /**
     * Prepare data for new report notification.
     *
     * @param BugReport $report
     * @return array
     */
    private function prepareNewReportData(BugReport $report): array
    {
        return [
            'report_id' => $report->id,
            'title' => $report->title,
            'description' => \Str::limit($report->description, 200),
            'type' => $report->type,
            'type_label' => $report->type_label,
            'severity' => $report->severity,
            'severity_label' => $report->severity_label,
            'reporter_name' => $report->reporter->name ?? 'Nieznany',
            'reporter_email' => $report->reporter->email ?? '',
            'context_url' => $report->context_url,
            'created_at' => $report->created_at->format('d.m.Y H:i'),
            'admin_url' => route('admin.bug-reports.show', $report),
        ];
    }

    /**
     * Prepare data for status change notification.
     *
     * @param BugReport $report
     * @param string $oldStatus
     * @return array
     */
    private function prepareStatusChangeData(BugReport $report, string $oldStatus): array
    {
        $oldStatusLabel = BugReport::getStatuses()[$oldStatus] ?? $oldStatus;

        return [
            'report_id' => $report->id,
            'title' => $report->title,
            'old_status' => $oldStatus,
            'old_status_label' => $oldStatusLabel,
            'new_status' => $report->status,
            'new_status_label' => $report->status_label,
            'timestamp' => now()->format('d.m.Y H:i'),
        ];
    }

    /**
     * Prepare data for resolution notification.
     *
     * @param BugReport $report
     * @return array
     */
    private function prepareResolutionData(BugReport $report): array
    {
        return [
            'report_id' => $report->id,
            'title' => $report->title,
            'status' => $report->status,
            'status_label' => $report->status_label,
            'resolution' => $report->resolution,
            'resolved_at' => $report->resolved_at?->format('d.m.Y H:i'),
            'reporter_name' => $report->reporter->name ?? 'Nieznany',
        ];
    }

    /**
     * Send email notification to admins about new report.
     *
     * @param array $data
     * @param array $emails
     * @return void
     */
    private function sendAdminEmailNotification(array $data, array $emails): void
    {
        $subject = $this->getNewReportEmailSubject($data);
        $body = $this->getNewReportEmailBody($data);

        foreach ($emails as $email) {
            try {
                Mail::raw($body, function ($message) use ($email, $subject) {
                    $message->to($email)->subject($subject);
                });
            } catch (\Exception $e) {
                Log::warning('Failed to send admin email', [
                    'email' => $email,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Send email notification to reporter about status change.
     *
     * @param array $data
     * @param string $email
     * @return void
     */
    private function sendReporterEmailNotification(array $data, string $email): void
    {
        $subject = "[PPM] Zgloszenie #{$data['report_id']} - zmiana statusu";

        $body = implode("\n", [
            "Twoje zgloszenie zostalo zaktualizowane",
            "=========================================",
            "",
            "Zgloszenie: #{$data['report_id']} - {$data['title']}",
            "",
            "Status zmieniony z: {$data['old_status_label']}",
            "Nowy status: {$data['new_status_label']}",
            "",
            "Data aktualizacji: {$data['timestamp']}",
            "",
            "---",
            "PPM - Prestashop Product Manager",
            config('app.url', 'https://ppm.mpptrade.pl'),
        ]);

        try {
            Mail::raw($body, function ($message) use ($email, $subject) {
                $message->to($email)->subject($subject);
            });
        } catch (\Exception $e) {
            Log::warning('Failed to send reporter email', [
                'email' => $email,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Send resolution email notification.
     *
     * @param array $data
     * @param string $email
     * @return void
     */
    private function sendResolutionEmailNotification(array $data, string $email): void
    {
        $statusText = $data['status'] === BugReport::STATUS_RESOLVED ? 'rozwiazane' : 'odrzucone';
        $subject = "[PPM] Zgloszenie #{$data['report_id']} - {$statusText}";

        $body = implode("\n", [
            "Twoje zgloszenie zostalo {$statusText}",
            "==========================================",
            "",
            "Zgloszenie: #{$data['report_id']} - {$data['title']}",
            "Status: {$data['status_label']}",
            "",
            "Rozwiazanie / Odpowiedz:",
            "------------------------",
            $data['resolution'] ?? 'Brak dodatkowych informacji',
            "",
            "Data: {$data['resolved_at']}",
            "",
            "---",
            "PPM - Prestashop Product Manager",
            config('app.url', 'https://ppm.mpptrade.pl'),
        ]);

        try {
            Mail::raw($body, function ($message) use ($email, $subject) {
                $message->to($email)->subject($subject);
            });
        } catch (\Exception $e) {
            Log::warning('Failed to send resolution email', [
                'email' => $email,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get email subject for new report.
     *
     * @param array $data
     * @return string
     */
    private function getNewReportEmailSubject(array $data): string
    {
        $severityIcon = match ($data['severity']) {
            'critical' => '[!!!]',
            'high' => '[!!]',
            'medium' => '[!]',
            default => '',
        };

        return "{$severityIcon} [PPM] Nowe zgloszenie: {$data['title']}";
    }

    /**
     * Get email body for new report.
     *
     * @param array $data
     * @return string
     */
    private function getNewReportEmailBody(array $data): string
    {
        $lines = [
            "Nowe zgloszenie w systemie PPM",
            "==============================",
            "",
            "ID: #{$data['report_id']}",
            "Tytul: {$data['title']}",
            "Typ: {$data['type_label']}",
            "Priorytet: {$data['severity_label']}",
            "",
            "Zglaszajacy: {$data['reporter_name']} ({$data['reporter_email']})",
            "Data: {$data['created_at']}",
        ];

        if (!empty($data['context_url'])) {
            $lines[] = "URL: {$data['context_url']}";
        }

        $lines[] = "";
        $lines[] = "Opis:";
        $lines[] = "-----";
        $lines[] = $data['description'];
        $lines[] = "";
        $lines[] = "Link do zgloszenia:";
        $lines[] = $data['admin_url'];
        $lines[] = "";
        $lines[] = "---";
        $lines[] = "PPM - Prestashop Product Manager";

        return implode("\n", $lines);
    }

    /**
     * Send Teams notification for new report.
     *
     * @param array $data
     * @param string $webhookUrl
     * @return void
     */
    private function sendTeamsNotification(array $data, string $webhookUrl): void
    {
        try {
            $themeColor = match ($data['severity']) {
                'critical' => 'dc2626',
                'high' => 'f59e0b',
                'medium' => '3b82f6',
                default => '6b7280',
            };

            $payload = [
                '@type' => 'MessageCard',
                '@context' => 'http://schema.org/extensions',
                'themeColor' => $themeColor,
                'summary' => "Nowe zgloszenie: {$data['title']}",
                'sections' => [
                    [
                        'activityTitle' => "Nowe zgloszenie #{$data['report_id']}",
                        'activitySubtitle' => "przez {$data['reporter_name']}",
                        'facts' => [
                            ['name' => 'Tytul', 'value' => $data['title']],
                            ['name' => 'Typ', 'value' => $data['type_label']],
                            ['name' => 'Priorytet', 'value' => $data['severity_label']],
                            ['name' => 'Data', 'value' => $data['created_at']],
                        ],
                        'text' => $data['description'],
                    ],
                ],
                'potentialAction' => [
                    [
                        '@type' => 'OpenUri',
                        'name' => 'Otworz zgloszenie',
                        'targets' => [
                            ['os' => 'default', 'uri' => $data['admin_url']],
                        ],
                    ],
                ],
            ];

            $this->teamsChannel->send($webhookUrl, $payload);

        } catch (\Exception $e) {
            Log::warning('Failed to send Teams notification', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Send Teams notification for resolved report.
     *
     * @param array $data
     * @param string $webhookUrl
     * @return void
     */
    private function sendTeamsResolutionNotification(array $data, string $webhookUrl): void
    {
        try {
            $themeColor = $data['status'] === BugReport::STATUS_RESOLVED ? '22c55e' : 'ef4444';
            $statusText = $data['status'] === BugReport::STATUS_RESOLVED ? 'Rozwiazane' : 'Odrzucone';

            $payload = [
                '@type' => 'MessageCard',
                '@context' => 'http://schema.org/extensions',
                'themeColor' => $themeColor,
                'summary' => "Zgloszenie #{$data['report_id']} - {$statusText}",
                'sections' => [
                    [
                        'activityTitle' => "Zgloszenie #{$data['report_id']} - {$statusText}",
                        'facts' => [
                            ['name' => 'Tytul', 'value' => $data['title']],
                            ['name' => 'Status', 'value' => $data['status_label']],
                            ['name' => 'Data', 'value' => $data['resolved_at']],
                        ],
                        'text' => \Str::limit($data['resolution'] ?? '', 200),
                    ],
                ],
            ];

            $this->teamsChannel->send($webhookUrl, $payload);

        } catch (\Exception $e) {
            Log::warning('Failed to send Teams resolution notification', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
