<?php

namespace App\Http\Livewire\Dashboard\Widgets;

use Livewire\Component;
use App\Models\UserSession;
use App\Models\LoginAttempt;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * SecurityAlerts Widget (Admin-only)
 *
 * Monitors security threats: multi-session users, brute-force login
 * attempts, and off-hours activity. Polls every 60 seconds.
 * Each alert has severity (high/medium) and contextual details.
 */
class SecurityAlerts extends Component
{
    public array $alerts = [];
    public int $highCount = 0;
    public int $mediumCount = 0;

    public function mount(): void
    {
        $this->loadAlerts();
    }

    /**
     * Polled every 60s via wire:poll.60s in blade.
     */
    public function loadAlerts(): void
    {
        $this->alerts = $this->collectAlerts();
        $this->highCount = collect($this->alerts)->where('severity', 'high')->count();
        $this->mediumCount = collect($this->alerts)->where('severity', 'medium')->count();
    }

    protected function collectAlerts(): array
    {
        $alerts = [];

        // 1. Multi-session users (>3 active sessions)
        $multiSession = UserSession::select('user_id', DB::raw('count(*) as session_count'))
            ->where('is_active', true)
            ->groupBy('user_id')
            ->having('session_count', '>', 3)
            ->get();

        foreach ($multiSession as $row) {
            $user = User::find($row->user_id);
            if (!$user) {
                continue;
            }

            $alerts[] = [
                'type' => 'multi_session',
                'severity' => $row->session_count > 5 ? 'high' : 'medium',
                'message' => "{$user->full_name} - {$row->session_count} aktywnych sesji",
                'timestamp' => now()->format('H:i'),
            ];
        }

        // 2. Failed logins from same IP (>5 in 24h)
        $failedLogins = LoginAttempt::failed()
            ->where('attempted_at', '>=', now()->subHours(24))
            ->select('ip_address', DB::raw('count(*) as attempts'))
            ->groupBy('ip_address')
            ->having('attempts', '>', 5)
            ->get();

        foreach ($failedLogins as $row) {
            $alerts[] = [
                'type' => 'brute_force',
                'severity' => 'high',
                'message' => "IP {$row->ip_address} - {$row->attempts} nieudanych prob logowania (24h)",
                'timestamp' => now()->format('H:i'),
            ];
        }

        // 3. Off-hours activity (before 6:00 or after 22:00, last 7 days)
        $offHoursCount = AuditLog::whereRaw('HOUR(created_at) < 6 OR HOUR(created_at) > 22')
            ->where('created_at', '>=', now()->subDays(7))
            ->count();

        if ($offHoursCount > 10) {
            $alerts[] = [
                'type' => 'off_hours',
                'severity' => 'medium',
                'message' => "{$offHoursCount} akcji poza godzinami pracy (7 dni)",
                'timestamp' => now()->format('H:i'),
            ];
        }

        // Sort by severity (high first), limit to 8
        return collect($alerts)
            ->sortBy(function ($alert) {
                return $alert['severity'] === 'high' ? 0 : 1;
            })
            ->take(8)
            ->values()
            ->toArray();
    }

    public function render()
    {
        return view('livewire.dashboard.widgets.security-alerts');
    }
}
