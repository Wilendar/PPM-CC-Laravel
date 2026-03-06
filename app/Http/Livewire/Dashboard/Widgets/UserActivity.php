<?php

namespace App\Http\Livewire\Dashboard\Widgets;

use Livewire\Component;
use App\Models\AuditLog;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class UserActivity extends Component
{
    public Collection $activities;

    public function mount(): void
    {
        $userId = Auth::id();

        $this->activities = $userId
            ? AuditLog::forUser($userId)
                ->whereNotIn('event', [
                    AuditLog::EVENT_LOGIN,
                    AuditLog::EVENT_LOGIN_FAILED,
                    AuditLog::EVENT_LOGOUT,
                ])
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get()
            : collect();
    }

    /**
     * Get icon color class for event type.
     */
    public function getEventIconClass(string $event): string
    {
        return match ($event) {
            AuditLog::EVENT_CREATED => 'bg-green-500/20 text-green-400',
            AuditLog::EVENT_UPDATED => 'bg-blue-500/20 text-blue-400',
            AuditLog::EVENT_DELETED,
            AuditLog::EVENT_BULK_DELETE => 'bg-red-500/20 text-red-400',
            AuditLog::EVENT_BULK_UPDATE,
            AuditLog::EVENT_BULK_EXPORT => 'bg-amber-500/20 text-amber-400',
            AuditLog::EVENT_IMPORTED => 'bg-teal-500/20 text-teal-400',
            AuditLog::EVENT_EXPORTED => 'bg-indigo-500/20 text-indigo-400',
            AuditLog::EVENT_SYNCED => 'bg-cyan-500/20 text-cyan-400',
            AuditLog::EVENT_MATCHED => 'bg-emerald-500/20 text-emerald-400',
            default => 'bg-gray-500/20 text-gray-400',
        };
    }

    /**
     * Get SVG icon path for event type.
     */
    public function getEventIconPath(string $event): string
    {
        return match ($event) {
            AuditLog::EVENT_CREATED => 'M12 6v6m0 0v6m0-6h6m-6 0H6',
            AuditLog::EVENT_UPDATED => 'M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z',
            AuditLog::EVENT_DELETED,
            AuditLog::EVENT_BULK_DELETE => 'M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16',
            AuditLog::EVENT_IMPORTED => 'M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12',
            AuditLog::EVENT_EXPORTED => 'M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l4 4m0 0l-4 4m4-4H8',
            AuditLog::EVENT_SYNCED => 'M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15',
            AuditLog::EVENT_MATCHED => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
            default => 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
        };
    }

    /**
     * Build activity description with Polish model names.
     */
    public function getActivityDescription(AuditLog $log): string
    {
        $eventDisplay = $log->event_display;
        $modelName = $this->getModelDisplayName($log->auditable_type);

        return "{$eventDisplay} {$modelName}" . ($log->auditable_id ? " #{$log->auditable_id}" : '');
    }

    /**
     * Get Polish display name for model class.
     */
    protected function getModelDisplayName(string $className): string
    {
        return match (class_basename($className)) {
            'Product' => 'produkt',
            'Category' => 'kategorie',
            'ProductMedia' => 'media',
            'ImportBatch' => 'import',
            'SyncJob' => 'sync job',
            'User' => 'uzytkownika',
            'AdminNotification' => 'komunikat',
            'PrestaShopShop' => 'sklep',
            default => class_basename($className),
        };
    }

    public function render()
    {
        return view('livewire.dashboard.widgets.user-activity');
    }
}
