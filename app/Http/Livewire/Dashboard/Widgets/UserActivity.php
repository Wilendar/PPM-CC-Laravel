<?php

namespace App\Http\Livewire\Dashboard\Widgets;

use Livewire\Component;
use App\Models\AuditLog;
use App\Models\UserSession;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class UserActivity extends Component
{
    public Collection $activities;
    public float $todaySessionHours = 0;
    public bool $usingSessionFallback = false;

    public function mount(): void
    {
        $userId = Auth::id();

        // Primary: AuditLog
        $this->activities = $userId
            ? AuditLog::forUser($userId)
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get()
            : collect();

        // Fallback: if no audit logs, show sessions as activity
        if ($this->activities->isEmpty() && $userId) {
            $this->activities = UserSession::forUser($userId)
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get();
            $this->usingSessionFallback = true;
        }

        $this->todaySessionHours = $this->calculateTodayHours($userId);
    }

    /**
     * Calculate total session hours for today.
     */
    protected function calculateTodayHours(?int $userId): float
    {
        if (!$userId) {
            return 0;
        }

        $sessions = UserSession::forUser($userId)
            ->whereDate('created_at', today())
            ->get();

        $totalMinutes = $sessions->sum(fn ($session) => $session->getDurationMinutes());

        return round($totalMinutes / 60, 1);
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
            AuditLog::EVENT_LOGIN => 'bg-purple-500/20 text-purple-400',
            AuditLog::EVENT_LOGOUT => 'bg-gray-500/20 text-gray-400',
            AuditLog::EVENT_BULK_UPDATE,
            AuditLog::EVENT_BULK_EXPORT => 'bg-amber-500/20 text-amber-400',
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
            AuditLog::EVENT_LOGIN => 'M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1',
            AuditLog::EVENT_LOGOUT => 'M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1',
            default => 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
        };
    }

    /**
     * Build activity description.
     */
    public function getActivityDescription(AuditLog $log): string
    {
        $eventDisplay = $log->event_display;
        $modelType = $log->short_model_type;

        return "{$eventDisplay} {$modelType}" . ($log->auditable_id ? " #{$log->auditable_id}" : '');
    }

    /**
     * Get description for session fallback display.
     */
    public function getSessionDescription(UserSession $session): string
    {
        $device = ucfirst($session->device_type ?? 'desktop');
        $browser = $session->browser ?? 'Unknown';
        return "Sesja {$device} ({$browser})";
    }

    /**
     * Get icon class for session fallback display.
     */
    public function getSessionIconClass(UserSession $session): string
    {
        return $session->is_active ? 'bg-emerald-500/20 text-emerald-400' : 'bg-gray-500/20 text-gray-400';
    }

    public function render()
    {
        return view('livewire.dashboard.widgets.user-activity');
    }
}
