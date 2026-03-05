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
        'email_product_changes' => true,
        'email_sync_status' => true,
        'email_security_alerts' => true,
        'email_system_updates' => false,
        'browser_product_changes' => false,
        'browser_sync_status' => true,
        'browser_security_alerts' => true,
        'browser_system_updates' => false,
    ];

    /**
     * Default preference values. Used as fallback when user has
     * no saved preferences or when new preference keys are added.
     */
    protected array $defaultPrefs = [
        'email_product_changes' => true,
        'email_sync_status' => true,
        'email_security_alerts' => true,
        'email_system_updates' => false,
        'browser_product_changes' => false,
        'browser_sync_status' => true,
        'browser_security_alerts' => true,
        'browser_system_updates' => false,
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
    // COMPUTED PROPERTIES
    // ==========================================

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
