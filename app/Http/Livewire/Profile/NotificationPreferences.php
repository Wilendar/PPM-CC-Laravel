<?php

namespace App\Http\Livewire\Profile;

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Profile: Notification Preferences & Inbox
 *
 * Two-tab interface:
 * - Inbox: view, read, delete Laravel notifications
 * - Settings: toggle notification preferences per channel/category
 *
 * Uses Laravel's built-in Notifiable trait on User model.
 * Preferences stored in users.notification_settings JSON column.
 */
class NotificationPreferences extends Component
{
    use WithPagination;

    // ==========================================
    // PROPERTIES
    // ==========================================

    public string $activeTab = 'inbox';

    public int $perPage = 15;

    public bool $showUnreadOnly = false;

    /**
     * Notification preferences keyed by channel_category.
     * Merged with defaults on mount().
     */
    public array $prefs = [
        // Produkty
        'email_product_changes' => true,
        'browser_product_changes' => false,
        // Import
        'email_import_ready' => true,
        'browser_import_ready' => true,
        'email_import_scheduled' => true,
        'browser_import_scheduled' => false,
        'email_import_published' => true,
        'browser_import_published' => true,
        // Synchronizacja
        'email_sync_status' => true,
        'browser_sync_status' => true,
        'email_sync_failed' => true,
        'browser_sync_failed' => true,
        // Bezpieczenstwo
        'email_security_alerts' => true,
        'browser_security_alerts' => true,
        'email_login_new_ip' => true,
        'browser_login_new_ip' => true,
        // System
        'email_system_updates' => false,
        'browser_system_updates' => false,
        'email_backup_completed' => false,
        'browser_backup_completed' => true,
        // Nowy uzytkownik
        'email_new_user_pending' => true,
        'browser_new_user_pending' => true,
    ];

    /**
     * Default preference values. Used as fallback when user has
     * no saved preferences or when new preference keys are added.
     */
    protected array $defaultPrefs = [
        // Produkty
        'email_product_changes' => true,
        'browser_product_changes' => false,
        // Import
        'email_import_ready' => true,
        'browser_import_ready' => true,
        'email_import_scheduled' => true,
        'browser_import_scheduled' => false,
        'email_import_published' => true,
        'browser_import_published' => true,
        // Synchronizacja
        'email_sync_status' => true,
        'browser_sync_status' => true,
        'email_sync_failed' => true,
        'browser_sync_failed' => true,
        // Bezpieczenstwo
        'email_security_alerts' => true,
        'browser_security_alerts' => true,
        'email_login_new_ip' => true,
        'browser_login_new_ip' => true,
        // System
        'email_system_updates' => false,
        'browser_system_updates' => false,
        'email_backup_completed' => false,
        'browser_backup_completed' => true,
        // Nowy uzytkownik
        'email_new_user_pending' => true,
        'browser_new_user_pending' => true,
    ];

    // ==========================================
    // LIFECYCLE
    // ==========================================

    public function mount(): void
    {
        $user = Auth::user();

        $savedSettings = $user->notification_settings ?? [];

        // Merge saved settings over defaults so new keys get default values
        $this->prefs = array_merge($this->defaultPrefs, $savedSettings);
    }

    // ==========================================
    // AUTO-SAVE ON PROPERTY CHANGE
    // ==========================================

    /**
     * Automatically persist preferences when any pref toggle changes.
     * Triggered by @entangle().live binding from Alpine toggle switches.
     */
    public function updated($property, $value): void
    {
        if (str_starts_with($property, 'prefs.')) {
            $sanitized = array_map(fn($v) => (bool) $v, $this->prefs);

            Auth::user()->update(['notification_settings' => $sanitized]);

            $this->prefs = $sanitized;

            $this->dispatch('preferences-saved');

            \Log::info('Notification preferences auto-saved', [
                'user_id' => Auth::id(),
                'changed_key' => $property,
            ]);
        }
    }

    // ==========================================
    // COMPUTED PROPERTIES
    // ==========================================

    /**
     * Visible notification categories filtered by user permissions.
     * Admin sees all; other users see only categories they have permission for.
     *
     * @return array<int, array>
     */
    #[Computed]
    public function visibleCategories(): array
    {
        $user = Auth::user();
        $allCategories = $this->getCategoryDefinitions();

        return array_filter($allCategories, function ($cat) use ($user) {
            if ($cat['permission'] === null) {
                return true;
            }
            if ($user->hasRole('Admin')) {
                return true;
            }
            return $user->can($cat['permission']);
        });
    }

    /**
     * Paginated notifications for the current user.
     * Optionally filtered to unread only.
     */
    #[Computed]
    public function notifications()
    {
        return Auth::user()
            ->notifications()
            ->when($this->showUnreadOnly, fn ($q) => $q->whereNull('read_at'))
            ->latest()
            ->paginate($this->perPage);
    }

    /**
     * Total unread notification count for badge display.
     */
    #[Computed]
    public function unreadCount(): int
    {
        return Auth::user()
            ->unreadNotifications()
            ->count();
    }

    // ==========================================
    // INBOX ACTIONS
    // ==========================================

    /**
     * Mark a single notification as read.
     */
    public function markAsRead(string $id): void
    {
        $notification = Auth::user()
            ->notifications()
            ->where('id', $id)
            ->first();

        if (!$notification) {
            session()->flash('error', 'Powiadomienie nie zostalo znalezione.');
            return;
        }

        if ($notification->read_at !== null) {
            return;
        }

        $notification->markAsRead();

        session()->flash('success', 'Powiadomienie oznaczone jako przeczytane.');
    }

    /**
     * Mark all unread notifications as read.
     */
    public function markAllAsRead(): void
    {
        $count = Auth::user()->unreadNotifications()->count();

        if ($count === 0) {
            session()->flash('info', 'Brak nieprzeczytanych powiadomien.');
            return;
        }

        Auth::user()->unreadNotifications->markAsRead();

        session()->flash('success', "Oznaczono {$count} powiadomien jako przeczytane.");

        \Log::info('User marked all notifications as read', [
            'user_id' => Auth::id(),
            'count' => $count,
        ]);
    }

    /**
     * Delete a single notification.
     */
    public function deleteNotification(string $id): void
    {
        $notification = Auth::user()
            ->notifications()
            ->where('id', $id)
            ->first();

        if (!$notification) {
            session()->flash('error', 'Powiadomienie nie zostalo znalezione.');
            return;
        }

        $notification->delete();

        session()->flash('success', 'Powiadomienie zostalo usuniete.');
    }

    // ==========================================
    // SETTINGS ACTIONS
    // ==========================================

    /**
     * Persist notification preferences to the user's JSON column.
     */
    public function savePreferences(): void
    {
        try {
            $user = Auth::user();

            // Ensure all values are boolean
            $sanitized = [];
            foreach ($this->prefs as $key => $value) {
                $sanitized[$key] = (bool) $value;
            }

            $user->update([
                'notification_settings' => $sanitized,
            ]);

            $this->prefs = $sanitized;

            session()->flash('success', 'Preferencje powiadomien zostaly zapisane.');

            \Log::info('Notification preferences updated', [
                'user_id' => $user->id,
                'prefs' => $sanitized,
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to save notification preferences', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);

            session()->flash('error', 'Wystapil blad podczas zapisywania preferencji.');
        }
    }

    // ==========================================
    // PAGINATION HOOKS
    // ==========================================

    /**
     * Reset pagination when toggling the unread filter.
     */
    public function updatingShowUnreadOnly(): void
    {
        $this->resetPage();
    }

    // ==========================================
    // HELPERS
    // ==========================================

    /**
     * Full list of notification category definitions.
     * Each entry maps to email_<key> and browser_<key> preference keys.
     *
     * @return array<int, array{key: string, label: string, description: string, icon: string, icon_color: string, permission: string|null, group: string|null}>
     */
    protected function getCategoryDefinitions(): array
    {
        return [
            [
                'key' => 'product_changes',
                'label' => 'Produkty',
                'description' => 'Zmiany w produktach, aktualizacje danych',
                'icon' => 'product',
                'icon_color' => 'emerald',
                'permission' => 'products.read',
                'group' => null,
            ],
            [
                'key' => 'import_ready',
                'label' => 'Import: Gotowy do publikacji',
                'description' => 'Produkt ma SKU i nazwe - gotowy do opublikowania',
                'icon' => 'import',
                'icon_color' => 'purple',
                'permission' => 'import.read',
                'group' => 'Import',
            ],
            [
                'key' => 'import_scheduled',
                'label' => 'Import: Data publikacji',
                'description' => 'Data publikacji produktu zostala ustawiona',
                'icon' => 'import',
                'icon_color' => 'purple',
                'permission' => 'import.read',
                'group' => 'Import',
            ],
            [
                'key' => 'import_published',
                'label' => 'Import: Opublikowany',
                'description' => 'Produkt zostal pomyslnie opublikowany',
                'icon' => 'import',
                'icon_color' => 'purple',
                'permission' => 'import.read',
                'group' => 'Import',
            ],
            [
                'key' => 'sync_status',
                'label' => 'Synchronizacja',
                'description' => 'Status synchronizacji PrestaShop i ERP',
                'icon' => 'sync',
                'icon_color' => 'blue',
                'permission' => 'shops.sync',
                'group' => null,
            ],
            [
                'key' => 'sync_failed',
                'label' => 'Sync: Blad synchronizacji',
                'description' => 'Nieudana synchronizacja - wymaga uwagi',
                'icon' => 'sync',
                'icon_color' => 'red',
                'permission' => 'shops.sync',
                'group' => null,
            ],
            [
                'key' => 'security_alerts',
                'label' => 'Bezpieczenstwo',
                'description' => 'Logowania, podejrzana aktywnosc, alerty',
                'icon' => 'security',
                'icon_color' => 'red',
                'permission' => null,
                'group' => null,
            ],
            [
                'key' => 'login_new_ip',
                'label' => 'Login z nowego IP',
                'description' => 'Powiadomienie o logowaniu z nieznanego adresu IP',
                'icon' => 'security',
                'icon_color' => 'amber',
                'permission' => null,
                'group' => null,
            ],
            [
                'key' => 'system_updates',
                'label' => 'System',
                'description' => 'Aktualizacje systemu, konserwacja',
                'icon' => 'system',
                'icon_color' => 'gray',
                'permission' => 'system.config',
                'group' => null,
            ],
            [
                'key' => 'backup_completed',
                'label' => 'Backup ukonczony',
                'description' => 'Backup bazy danych ukonczony pomyslnie',
                'icon' => 'system',
                'icon_color' => 'gray',
                'permission' => 'system.config',
                'group' => null,
            ],
            [
                'key' => 'new_user_pending',
                'label' => 'Nowy uzytkownik',
                'description' => 'Nowy uzytkownik oczekuje na zatwierdzenie',
                'icon' => 'security',
                'icon_color' => 'blue',
                'permission' => 'system.config',
                'group' => null,
            ],
        ];
    }

    /**
     * Resolve full Tailwind classes for category icon background and text color.
     * Uses static map to avoid Tailwind purge issues with dynamic class names.
     *
     * @return array{bg: string, text: string}
     */
    public function getCategoryIconClasses(string $color): array
    {
        $map = [
            'emerald' => ['bg' => 'bg-emerald-500/20', 'text' => 'text-emerald-400'],
            'purple'  => ['bg' => 'bg-purple-500/20',  'text' => 'text-purple-400'],
            'blue'    => ['bg' => 'bg-blue-500/20',    'text' => 'text-blue-400'],
            'red'     => ['bg' => 'bg-red-500/20',     'text' => 'text-red-400'],
            'amber'   => ['bg' => 'bg-amber-500/20',   'text' => 'text-amber-400'],
            'gray'    => ['bg' => 'bg-gray-500/20',    'text' => 'text-gray-400'],
        ];

        return $map[$color] ?? $map['gray'];
    }

    /**
     * Resolve a human-readable title from notification data or type.
     */
    public function getNotificationTitle(object $notification): string
    {
        if (!empty($notification->data['title'])) {
            return $notification->data['title'];
        }

        // Fallback: extract short class name from FQCN type string
        $type = $notification->type;
        $basename = class_basename($type);

        // Convert PascalCase to words: ProductSyncNotification -> Product Sync Notification
        return trim(preg_replace('/([A-Z])/', ' $1', $basename));
    }

    /**
     * Resolve the description/message body from notification data.
     */
    public function getNotificationMessage(object $notification): string
    {
        return $notification->data['message']
            ?? $notification->data['body']
            ?? $notification->data['description']
            ?? '';
    }

    /**
     * Map notification type to an icon identifier for the Blade view.
     */
    public function getNotificationIcon(object $notification): string
    {
        $type = strtolower($notification->type);

        if (str_contains($type, 'sync')) {
            return 'sync';
        }
        if (str_contains($type, 'product')) {
            return 'product';
        }
        if (str_contains($type, 'security') || str_contains($type, 'login')) {
            return 'security';
        }
        if (str_contains($type, 'import') || str_contains($type, 'export')) {
            return 'import';
        }
        if (str_contains($type, 'stock')) {
            return 'stock';
        }
        if (str_contains($type, 'maintenance') || str_contains($type, 'system')) {
            return 'system';
        }

        return 'default';
    }

    // ==========================================
    // RENDER
    // ==========================================

    public function render()
    {
        return view('livewire.profile.notification-preferences')
            ->layout('layouts.admin', [
                'title' => 'Powiadomienia - Admin PPM',
                'breadcrumb' => 'Powiadomienia',
            ]);
    }
}
