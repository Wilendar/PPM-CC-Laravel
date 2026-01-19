<?php

namespace App\Services\Channels;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Microsoft Teams Webhook Channel - 2.2.1.2.3 (Updated for Power Automate)
 *
 * Handles sending notifications to Microsoft Teams via:
 * - Legacy Incoming Webhook (MessageCard) - deprecated Dec 31, 2025
 * - Power Automate Workflows (Adaptive Cards) - recommended
 *
 * Auto-detects webhook type based on URL pattern.
 *
 * @see https://learn.microsoft.com/en-us/power-automate/teams/teams-app
 * @see https://adaptivecards.io/designer/
 */
class TeamsChannel
{
    /**
     * Default timeout for webhook requests in seconds.
     */
    private const WEBHOOK_TIMEOUT = 10;

    /**
     * Send a message to Teams webhook (auto-detects format).
     *
     * @param string $webhookUrl The Teams webhook URL
     * @param array $card Card payload (will be converted to appropriate format)
     * @return bool Success status
     */
    public function send(string $webhookUrl, array $card): bool
    {
        try {
            // Detect webhook type and format payload accordingly
            $payload = $this->isWorkflowsWebhook($webhookUrl)
                ? $this->wrapAsAdaptiveCard($card)
                : $card;

            $response = Http::timeout(self::WEBHOOK_TIMEOUT)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                ])
                ->post($webhookUrl, $payload);

            if ($response->successful()) {
                Log::debug('Teams webhook message sent successfully', [
                    'status' => $response->status(),
                    'type' => $this->isWorkflowsWebhook($webhookUrl) ? 'workflows' : 'legacy',
                ]);
                return true;
            }

            Log::warning('Teams webhook request failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            return false;

        } catch (\Exception $e) {
            Log::error('Teams webhook exception', [
                'error' => $e->getMessage(),
                'webhook_url' => $this->maskWebhookUrl($webhookUrl),
            ]);
            return false;
        }
    }

    /**
     * Send a test message to verify webhook connection.
     *
     * @param string $webhookUrl The Teams webhook URL
     * @return array Result with success status and optional error message
     */
    public function sendTestMessage(string $webhookUrl): array
    {
        $isWorkflows = $this->isWorkflowsWebhook($webhookUrl);
        $card = $isWorkflows
            ? $this->formatTestAdaptiveCard()
            : $this->formatTestMessageCard();

        $success = $this->send($webhookUrl, $card);

        return [
            'success' => $success,
            'error' => $success ? null : 'Nie udalo sie wyslac wiadomosci testowej do Teams',
            'type' => $isWorkflows ? 'Power Automate Workflows' : 'Legacy Webhook (deprecated)',
        ];
    }

    /**
     * Send a sync notification to Teams.
     *
     * @param string $webhookUrl The Teams webhook URL
     * @param array $data Sync job data including status, job_name, shop_name, etc.
     * @return bool Success status
     */
    public function sendSyncNotification(string $webhookUrl, array $data): bool
    {
        $isWorkflows = $this->isWorkflowsWebhook($webhookUrl);
        $card = $isWorkflows
            ? $this->formatSyncAdaptiveCard($data)
            : $this->formatSyncMessageCard($data);

        return $this->send($webhookUrl, $card);
    }

    /**
     * Detect if webhook URL is Power Automate Workflows type.
     *
     * Power Automate URLs contain: logic.azure.com or prod-XX.westeurope.logic.azure.com
     * Legacy webhooks contain: outlook.office.com/webhook
     *
     * @param string $webhookUrl
     * @return bool
     */
    private function isWorkflowsWebhook(string $webhookUrl): bool
    {
        return str_contains($webhookUrl, 'logic.azure.com')
            || str_contains($webhookUrl, 'webhook.office.com')
            || !str_contains($webhookUrl, 'outlook.office.com/webhook');
    }

    /**
     * Wrap Adaptive Card in message format for Power Automate.
     *
     * @param array $card Adaptive Card or MessageCard
     * @return array Wrapped payload
     */
    private function wrapAsAdaptiveCard(array $card): array
    {
        // If already an Adaptive Card structure, wrap it
        if (isset($card['type']) && $card['type'] === 'AdaptiveCard') {
            return [
                'type' => 'message',
                'attachments' => [
                    [
                        'contentType' => 'application/vnd.microsoft.card.adaptive',
                        'contentUrl' => null,
                        'content' => $card,
                    ],
                ],
            ];
        }

        // If it's a raw adaptive card body, wrap it
        return $card;
    }

    // ========================================
    // ADAPTIVE CARDS (Power Automate Workflows)
    // ========================================

    /**
     * Format a test connection Adaptive Card.
     *
     * @return array Adaptive Card payload
     */
    private function formatTestAdaptiveCard(): array
    {
        return [
            'type' => 'message',
            'attachments' => [
                [
                    'contentType' => 'application/vnd.microsoft.card.adaptive',
                    'contentUrl' => null,
                    'content' => [
                        '$schema' => 'http://adaptivecards.io/schemas/adaptive-card.json',
                        'type' => 'AdaptiveCard',
                        'version' => '1.4',
                        'body' => [
                            [
                                'type' => 'TextBlock',
                                'size' => 'Large',
                                'weight' => 'Bolder',
                                'text' => 'PPM - Test polaczenia',
                                'color' => 'Accent',
                            ],
                            [
                                'type' => 'TextBlock',
                                'text' => now()->format('Y-m-d H:i:s'),
                                'isSubtle' => true,
                                'spacing' => 'None',
                            ],
                            [
                                'type' => 'FactSet',
                                'facts' => [
                                    ['title' => 'Status', 'value' => 'Polaczenie dziala!'],
                                    ['title' => 'System', 'value' => 'PPM Sync Manager'],
                                    ['title' => 'Serwer', 'value' => config('app.url', 'PPM')],
                                ],
                            ],
                        ],
                        'actions' => [
                            [
                                'type' => 'Action.OpenUrl',
                                'title' => 'Otworz PPM',
                                'url' => config('app.url', 'https://ppm.mpptrade.pl') . '/admin/shops/sync',
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Format a sync notification Adaptive Card.
     *
     * @param array $data Sync data
     * @return array Adaptive Card payload
     */
    private function formatSyncAdaptiveCard(array $data): array
    {
        $status = $data['status'] ?? 'unknown';
        $isSuccess = $status === 'success';

        // Status icon and color
        $statusConfig = match ($status) {
            'success' => ['icon' => '✅', 'color' => 'Good'],
            'failure' => ['icon' => '❌', 'color' => 'Attention'],
            'retry_exhausted' => ['icon' => '⚠️', 'color' => 'Warning'],
            default => ['icon' => 'ℹ️', 'color' => 'Default'],
        };

        $facts = [
            ['title' => 'Status', 'value' => ucfirst($status)],
            ['title' => 'Sklep', 'value' => $data['shop_name'] ?? 'Nieznany'],
            ['title' => 'Czas', 'value' => $data['duration'] ?? 'N/A'],
            ['title' => 'Produktow', 'value' => (string)($data['products_count'] ?? 0)],
        ];

        // Add error message for failures
        if (!$isSuccess && !empty($data['message'])) {
            $facts[] = ['title' => 'Blad', 'value' => substr($data['message'], 0, 200)];
        }

        $body = [
            [
                'type' => 'TextBlock',
                'size' => 'Large',
                'weight' => 'Bolder',
                'text' => "{$statusConfig['icon']} {$data['job_name']}",
                'color' => $statusConfig['color'],
            ],
            [
                'type' => 'TextBlock',
                'text' => now()->format('Y-m-d H:i:s'),
                'isSubtle' => true,
                'spacing' => 'None',
            ],
            [
                'type' => 'FactSet',
                'facts' => $facts,
            ],
        ];

        return [
            'type' => 'message',
            'attachments' => [
                [
                    'contentType' => 'application/vnd.microsoft.card.adaptive',
                    'contentUrl' => null,
                    'content' => [
                        '$schema' => 'http://adaptivecards.io/schemas/adaptive-card.json',
                        'type' => 'AdaptiveCard',
                        'version' => '1.4',
                        'body' => $body,
                        'actions' => [
                            [
                                'type' => 'Action.OpenUrl',
                                'title' => 'Otworz PPM',
                                'url' => config('app.url', 'https://ppm.mpptrade.pl') . '/admin/shops/sync',
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    // ========================================
    // MESSAGE CARDS (Legacy - deprecated Dec 2025)
    // ========================================

    /**
     * Format a test connection MessageCard (Legacy).
     *
     * @return array MessageCard payload
     */
    private function formatTestMessageCard(): array
    {
        return [
            '@type' => 'MessageCard',
            '@context' => 'http://schema.org/extensions',
            'themeColor' => '0076D7',
            'summary' => 'PPM Test Connection',
            'sections' => [
                [
                    'activityTitle' => 'PPM - Test polaczenia',
                    'activitySubtitle' => now()->format('Y-m-d H:i:s'),
                    'activityImage' => 'https://adaptivecards.io/content/cats/1.png',
                    'facts' => [
                        ['name' => 'Status', 'value' => 'Polaczenie dziala!'],
                        ['name' => 'System', 'value' => 'PPM Sync Manager'],
                        ['name' => 'Serwer', 'value' => config('app.url', 'PPM')],
                    ],
                    'markdown' => true,
                ],
            ],
        ];
    }

    /**
     * Format a sync notification MessageCard (Legacy).
     *
     * @param array $data Sync data
     * @return array MessageCard payload
     */
    private function formatSyncMessageCard(array $data): array
    {
        $isSuccess = ($data['status'] ?? 'unknown') === 'success';
        $status = $data['status'] ?? 'unknown';

        $themeColor = match ($status) {
            'success' => '00FF00',
            'failure' => 'FF0000',
            'retry_exhausted' => 'FFA500',
            default => '808080',
        };

        $statusEmoji = match ($status) {
            'success' => '(tick)',
            'failure' => '(error)',
            'retry_exhausted' => '(warning)',
            default => '(info)',
        };

        $facts = [
            ['name' => 'Status', 'value' => ucfirst($status)],
            ['name' => 'Sklep', 'value' => $data['shop_name'] ?? 'Nieznany'],
            ['name' => 'Czas', 'value' => $data['duration'] ?? 'N/A'],
            ['name' => 'Produktow', 'value' => (string)($data['products_count'] ?? 0)],
        ];

        if (!$isSuccess && !empty($data['message'])) {
            $facts[] = ['name' => 'Blad', 'value' => substr($data['message'], 0, 200)];
        }

        return [
            '@type' => 'MessageCard',
            '@context' => 'http://schema.org/extensions',
            'themeColor' => $themeColor,
            'summary' => "PPM Sync: {$data['job_name']}",
            'sections' => [
                [
                    'activityTitle' => "{$statusEmoji} {$data['job_name']}",
                    'activitySubtitle' => now()->format('Y-m-d H:i:s'),
                    'facts' => $facts,
                    'markdown' => true,
                ],
            ],
            'potentialAction' => [
                [
                    '@type' => 'OpenUri',
                    'name' => 'Otworz PPM',
                    'targets' => [
                        ['os' => 'default', 'uri' => config('app.url', 'https://ppm.mpptrade.pl') . '/admin/shops/sync'],
                    ],
                ],
            ],
        ];
    }

    /**
     * Mask webhook URL for logging (security).
     *
     * @param string $url Full webhook URL
     * @return string Masked URL showing only domain
     */
    private function maskWebhookUrl(string $url): string
    {
        $parsed = parse_url($url);
        if ($parsed && isset($parsed['host'])) {
            return $parsed['scheme'] . '://' . $parsed['host'] . '/...';
        }
        return 'invalid-url';
    }
}
