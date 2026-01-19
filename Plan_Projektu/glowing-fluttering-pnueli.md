# Plan: Rozbudowa Konfiguracji Powiadomień Synchronizacji

**Data:** 2026-01-19
**Status:** Do implementacji
**Poprzedni plan:** System Deduplikacji Jobów (UKOŃCZONY)

---

## Cel

Rozbudowa sekcji NOTIFICATIONS (2.2.1.2.3) w panelu `/admin/shops/sync`:
1. Dodać pole do zarządzania email recipients
2. Dodać integrację z Microsoft Teams (webhook)
3. Zaimplementować faktyczne wysyłanie powiadomień przy sync success/failure

---

## Analiza obecnego stanu

### Co działa:
| Sekcja | Status |
|--------|--------|
| 2.2.1.2.1 Auto-sync Scheduler | ✅ OK |
| 2.2.1.2.2 Retry Logic | ✅ OK |
| 2.2.1.2.3 Notifications | 🔴 BRAKI |
| 2.2.1.2.4 Performance | ✅ OK |
| 2.2.1.2.5 Backup | ✅ OK |

### Problemy w sekcji Notifications:
1. **BRAK pola UI** dla email recipients (property `$notificationRecipients` istnieje, ale jest pusta!)
2. **Slack checkbox** istnieje w UI, ale brak implementacji wysyłania
3. **BRAK Teams** - ani UI ani backend
4. **BRAK triggera** - SyncJob nie wywołuje powiadomień przy success/failure

---

## ZADANIE 1: UI - Rozbudowa sekcji Notifications

**Plik:** `resources/views/livewire/admin/shops/sync-controller.blade.php` (linie ~482-541)

### 1.1 Dodać sekcję Email Recipients

```blade
<!-- Email Recipients -->
<div>
    <label class="block text-sm font-medium text-gray-300 mb-2">Odbiorcy Email</label>
    <div x-data="{ emails: @entangle('emailRecipients'), newEmail: '' }">
        <!-- Lista obecnych odbiorców -->
        <div class="space-y-2 mb-2">
            <template x-for="(email, index) in emails" :key="index">
                <div class="flex items-center gap-2">
                    <span class="text-sm text-white bg-gray-700 px-3 py-1 rounded" x-text="email"></span>
                    <button @click="emails.splice(index, 1)" type="button" class="text-red-400 hover:text-red-300">
                        <svg class="w-4 h-4">...</svg>
                    </button>
                </div>
            </template>
        </div>
        <!-- Input do dodawania -->
        <div class="flex gap-2">
            <input type="email" x-model="newEmail" placeholder="email@example.com"
                   class="flex-1 px-3 py-2 bg-gray-800 border border-gray-600 text-white rounded-lg text-sm">
            <button @click="if(newEmail && newEmail.includes('@')) { emails.push(newEmail); newEmail = '' }"
                    type="button" class="btn-enterprise-secondary">Dodaj</button>
        </div>
    </div>
</div>
```

### 1.2 Dodać checkbox Teams + Webhook URL

```blade
<!-- Teams channel -->
<label class="flex items-center">
    <input type="checkbox" wire:model.live="teamsEnabled" class="checkbox-enterprise">
    <span class="ml-2 text-sm text-white">Microsoft Teams</span>
</label>

@if($teamsEnabled)
<div class="mt-2">
    <label class="block text-sm font-medium text-gray-300 mb-1">Teams Webhook URL</label>
    <input type="url" wire:model.live="teamsWebhookUrl"
           placeholder="https://outlook.office.com/webhook/..."
           class="w-full px-3 py-2 bg-gray-800 border border-gray-600 text-white rounded-lg text-sm">
    <button wire:click="testTeamsWebhook" type="button" class="mt-2 btn-enterprise-secondary text-xs">
        Test połączenia
    </button>
</div>
@endif
```

---

## ZADANIE 2: Backend - Rozszerzenie SyncController

**Plik:** `app/Http/Livewire/Admin/Shops/SyncController.php`

### 2.1 Nowe properties (dodać po linii ~104)

```php
// Teams webhook configuration - 2.2.1.2.3
public $teamsEnabled = false;
public $teamsWebhookUrl = '';

// Email recipients (explicit property for UI binding)
public $emailRecipients = [];
```

### 2.2 Rozszerzyć rules() (dodać do sekcji Notifications)

```php
// Teams webhook
'teamsEnabled' => 'boolean',
'teamsWebhookUrl' => 'nullable|url|required_if:teamsEnabled,true',

// Email recipients
'emailRecipients' => 'array',
'emailRecipients.*' => 'email',
```

### 2.3 Rozszerzyć mapSettingToProperty() (dodać mapowania)

```php
'sync.notifications.teams_enabled' => 'teamsEnabled',
'sync.notifications.teams_webhook_url' => 'teamsWebhookUrl',
'sync.notifications.email_recipients' => 'emailRecipients',
```

### 2.4 Rozszerzyć saveSyncConfiguration() (dodać klucze)

```php
// W $settings array dodać:
'sync.notifications.teams_enabled' => $this->teamsEnabled,
'sync.notifications.teams_webhook_url' => $this->teamsWebhookUrl,
'sync.notifications.email_recipients' => $this->emailRecipients,
```

### 2.5 Nowa metoda testTeamsWebhook()

```php
public function testTeamsWebhook(): void
{
    try {
        if (empty($this->teamsWebhookUrl)) {
            $this->dispatch('notify', ['type' => 'error', 'message' => 'Brak URL webhook']);
            return;
        }

        $teamsChannel = app(\App\Services\Channels\TeamsChannel::class);
        $result = $teamsChannel->sendTestMessage($this->teamsWebhookUrl);

        if ($result['success']) {
            $this->dispatch('notify', ['type' => 'success', 'message' => 'Połączenie Teams OK!']);
        } else {
            $this->dispatch('notify', ['type' => 'error', 'message' => $result['error']]);
        }
    } catch (\Exception $e) {
        $this->dispatch('notify', ['type' => 'error', 'message' => 'Błąd: ' . $e->getMessage()]);
    }
}
```

### 2.6 Rozszerzyć validateNotificationConfig()

```php
// Dodać walidację:
if ($this->teamsEnabled && empty($this->teamsWebhookUrl)) {
    return ['valid' => false, 'message' => 'Teams włączony ale brak webhook URL'];
}

if (in_array('email', $this->notificationChannels) && empty($this->emailRecipients)) {
    return ['valid' => false, 'message' => 'Email włączony ale brak odbiorców'];
}
```

---

## ZADANIE 3: TeamsChannel Service

**Nowy plik:** `app/Services/Channels/TeamsChannel.php`

```php
<?php

namespace App\Services\Channels;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TeamsChannel
{
    public function send(string $webhookUrl, array $card): bool
    {
        try {
            $response = Http::timeout(10)->post($webhookUrl, $card);
            return $response->successful();
        } catch (\Exception $e) {
            Log::error('Teams webhook failed', ['error' => $e->getMessage()]);
            return false;
        }
    }

    public function sendTestMessage(string $webhookUrl): array
    {
        $card = $this->formatTestCard();
        $success = $this->send($webhookUrl, $card);

        return [
            'success' => $success,
            'error' => $success ? null : 'Nie udało się wysłać wiadomości'
        ];
    }

    public function sendSyncNotification(string $webhookUrl, array $data): bool
    {
        $card = $this->formatSyncCard($data);
        return $this->send($webhookUrl, $card);
    }

    private function formatTestCard(): array
    {
        return [
            '@type' => 'MessageCard',
            '@context' => 'http://schema.org/extensions',
            'themeColor' => '0076D7',
            'summary' => 'PPM Test Connection',
            'sections' => [[
                'activityTitle' => '🔔 PPM - Test połączenia',
                'activitySubtitle' => now()->format('Y-m-d H:i:s'),
                'facts' => [
                    ['name' => 'Status', 'value' => 'Połączenie działa!'],
                    ['name' => 'System', 'value' => 'PPM Sync Manager'],
                ]
            ]]
        ];
    }

    private function formatSyncCard(array $data): array
    {
        $isSuccess = $data['status'] === 'success';
        return [
            '@type' => 'MessageCard',
            '@context' => 'http://schema.org/extensions',
            'themeColor' => $isSuccess ? '00FF00' : 'FF0000',
            'summary' => "PPM Sync: {$data['job_name']}",
            'sections' => [[
                'activityTitle' => ($isSuccess ? '✅' : '❌') . " {$data['job_name']}",
                'activitySubtitle' => $data['shop_name'] ?? 'Unknown Shop',
                'facts' => [
                    ['name' => 'Status', 'value' => $data['status']],
                    ['name' => 'Czas', 'value' => $data['duration'] ?? 'N/A'],
                    ['name' => 'Produktów', 'value' => (string)($data['products_count'] ?? 0)],
                ],
                'text' => $data['message'] ?? ''
            ]]
        ];
    }
}
```

---

## ZADANIE 4: SyncNotificationService

**Nowy plik:** `app/Services/SyncNotificationService.php`

```php
<?php

namespace App\Services;

use App\Models\SyncJob;
use App\Models\SystemSetting;
use App\Services\Channels\TeamsChannel;
use App\Jobs\SendNotificationJob;
use Illuminate\Support\Facades\Log;

class SyncNotificationService
{
    public function __construct(
        private TeamsChannel $teamsChannel
    ) {}

    public function sendSyncNotification(SyncJob $syncJob, string $event): void
    {
        // event: 'success', 'failure', 'retry_exhausted'

        $settings = $this->getNotificationSettings();

        // Sprawdź czy powiadomienie dla tego eventu jest włączone
        if (!$this->shouldNotify($settings, $event)) {
            return;
        }

        $data = $this->prepareSyncData($syncJob, $event);

        // Email
        if (in_array('email', $settings['channels']) && !empty($settings['email_recipients'])) {
            $this->sendEmailNotifications($data, $settings['email_recipients']);
        }

        // Teams
        if ($settings['teams_enabled'] && !empty($settings['teams_webhook_url'])) {
            $this->teamsChannel->sendSyncNotification($settings['teams_webhook_url'], $data);
        }

        Log::info('Sync notification sent', [
            'sync_job_id' => $syncJob->id,
            'event' => $event,
            'channels' => $settings['channels'],
        ]);
    }

    private function getNotificationSettings(): array
    {
        return [
            'enabled' => SystemSetting::get('sync.notifications.enabled', true),
            'on_success' => SystemSetting::get('sync.notifications.on_success', false),
            'on_failure' => SystemSetting::get('sync.notifications.on_failure', true),
            'on_retry_exhausted' => SystemSetting::get('sync.notifications.on_retry_exhausted', true),
            'channels' => SystemSetting::get('sync.notifications.channels', ['email']),
            'email_recipients' => SystemSetting::get('sync.notifications.email_recipients', []),
            'teams_enabled' => SystemSetting::get('sync.notifications.teams_enabled', false),
            'teams_webhook_url' => SystemSetting::get('sync.notifications.teams_webhook_url', ''),
        ];
    }

    private function shouldNotify(array $settings, string $event): bool
    {
        if (!$settings['enabled']) return false;

        return match($event) {
            'success' => $settings['on_success'],
            'failure' => $settings['on_failure'],
            'retry_exhausted' => $settings['on_retry_exhausted'],
            default => false,
        };
    }

    private function prepareSyncData(SyncJob $syncJob, string $event): array
    {
        return [
            'job_name' => $syncJob->job_name,
            'shop_name' => $syncJob->prestashopShop?->name ?? 'Unknown',
            'status' => $event,
            'duration' => $syncJob->duration_seconds ? "{$syncJob->duration_seconds}s" : 'N/A',
            'products_count' => $syncJob->result_summary['synced'] ?? 0,
            'message' => $event === 'failure' ? ($syncJob->error_message ?? 'Unknown error') : '',
        ];
    }

    private function sendEmailNotifications(array $data, array $recipients): void
    {
        foreach ($recipients as $email) {
            SendNotificationJob::dispatch([
                'type' => 'sync_' . $data['status'],
                'title' => "Sync: {$data['job_name']}",
                'message' => "Status: {$data['status']}, Shop: {$data['shop_name']}",
                'recipients' => [$email],
            ]);
        }
    }
}
```

---

## ZADANIE 5: Trigger w Sync Job-ach

### 5.1 PullProductsFromPrestaShop.php

**Plik:** `app/Jobs/PullProductsFromPrestaShop.php`

Po zakończeniu handle() (przed `return`):

```php
// Po $this->syncJob->complete() lub similar:
try {
    app(\App\Services\SyncNotificationService::class)
        ->sendSyncNotification($this->syncJob, 'success');
} catch (\Exception $e) {
    Log::warning('Failed to send sync notification', ['error' => $e->getMessage()]);
}
```

W metodzie `failed()`:

```php
public function failed(\Throwable $exception): void
{
    // ... existing code ...

    try {
        $event = $this->attempts() >= $this->tries ? 'retry_exhausted' : 'failure';
        app(\App\Services\SyncNotificationService::class)
            ->sendSyncNotification($this->syncJob, $event);
    } catch (\Exception $e) {
        Log::warning('Failed to send failure notification', ['error' => $e->getMessage()]);
    }
}
```

### 5.2 SyncProductsJob.php (analogicznie)

**Plik:** `app/Jobs/PrestaShop/SyncProductsJob.php`

Dodać ten sam pattern co w 5.1.

---

## Pliki do modyfikacji/utworzenia

| Plik | Akcja | Opis |
|------|-------|------|
| `app/Http/Livewire/Admin/Shops/SyncController.php` | MODIFY | Nowe properties, rules, mapowania, testTeamsWebhook() |
| `resources/views/livewire/admin/shops/sync-controller.blade.php` | MODIFY | UI: email recipients, Teams checkbox+URL, test button |
| `app/Services/Channels/TeamsChannel.php` | CREATE | Teams webhook integration |
| `app/Services/SyncNotificationService.php` | CREATE | Centralized notification sending |
| `app/Jobs/PullProductsFromPrestaShop.php` | MODIFY | Trigger notifications |
| `app/Jobs/PrestaShop/SyncProductsJob.php` | MODIFY | Trigger notifications |

---

## Weryfikacja

### Po implementacji:

1. **Test UI:**
   - Czy pola email recipients wyświetlają się i pozwalają dodawać/usuwać
   - Czy checkbox Teams pokazuje pole webhook URL
   - Czy przycisk "Test połączenia" działa

2. **Test zapisywania:**
   - Zapisz konfigurację
   - Odśwież stronę
   - Sprawdź czy wartości się zachowały

3. **Test Teams webhook:**
   - Wklej prawdziwy webhook URL
   - Kliknij "Test połączenia"
   - Sprawdź czy wiadomość dotarła na Teams

4. **Test triggerów:**
   - Uruchom sync z powiadomieniami włączonymi
   - Sprawdź czy email/Teams otrzymali powiadomienie

### Chrome DevTools Verification:

```javascript
// 1. Navigate
mcp__claude-in-chrome__navigate({ tabId: TAB_ID, url: "https://ppm.mpptrade.pl/admin/shops/sync" })

// 2. Rozwiń zaawansowaną konfigurację
mcp__claude-in-chrome__find({ tabId: TAB_ID, query: "Pokaż zaawansowaną konfigurację" })

// 3. Screenshot sekcji Notifications
mcp__claude-in-chrome__computer({ tabId: TAB_ID, action: "screenshot" })

// 4. Test dodawania email
mcp__claude-in-chrome__find({ tabId: TAB_ID, query: "Odbiorcy Email" })
```

---

## Szacowany czas: 3-4h implementacji
