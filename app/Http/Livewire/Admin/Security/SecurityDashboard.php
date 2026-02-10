<?php

namespace App\Http\Livewire\Admin\Security;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\AuditLog;
use App\Models\BlockedIp;
use App\Models\UserSession;
use App\Models\LoginAttempt;
use App\Models\SecurityAlert;
use App\Models\PasswordPolicy;
use App\Models\User;
use App\Services\User\SessionManagementService;
use App\Services\User\PasswordPolicyService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

/**
 * ETAP_04 FAZA A: Security Dashboard Component
 *
 * Central security monitoring dashboard with:
 * - Active sessions overview
 * - Failed login tracking
 * - Security alerts management
 * - Password policy monitoring
 */
class SecurityDashboard extends Component
{
    use WithPagination;

    // ==========================================
    // PROPERTIES
    // ==========================================

    public string $activeTab = 'overview';

    // Overview stats
    public int $activeSessionsCount = 0;
    public int $failedLoginsToday = 0;
    public int $suspiciousActivities = 0;
    public int $lockedAccounts = 0;
    public int $expiringPasswords = 0;

    // Alerts
    public Collection $recentAlerts;
    public Collection $topAttackingIPs;
    public Collection $lockedOutUsers;
    public Collection $usersWithExpiringPasswords;

    // Filters
    public string $alertFilter = 'all';
    public int $alertsPerPage = 10;

    // IP Blocking
    public bool $showBlockIpModal = false;
    public string $blockIpAddress = '';
    public string $blockIpReason = '';
    public string $blockIpExpiry = '';

    // ==========================================
    // LIFECYCLE
    // ==========================================

    public function mount(): void
    {
        $this->recentAlerts = collect();
        $this->topAttackingIPs = collect();
        $this->lockedOutUsers = collect();
        $this->usersWithExpiringPasswords = collect();

        $this->refreshStats();
    }

    // ==========================================
    // TAB MANAGEMENT
    // ==========================================

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
        $this->resetPage();
    }

    // ==========================================
    // DATA REFRESH
    // ==========================================

    public function refreshStats(): void
    {
        // Active sessions
        $this->activeSessionsCount = UserSession::active()->count();

        // Failed logins today
        $this->failedLoginsToday = LoginAttempt::failed()->today()->count();

        // Suspicious activities
        $this->suspiciousActivities = UserSession::suspicious()->count();

        // Locked accounts
        $this->lockedAccounts = User::whereNotNull('locked_until')
            ->where('locked_until', '>', now())
            ->count();

        // Expiring passwords
        $passwordService = app(PasswordPolicyService::class);
        $this->expiringPasswords = $passwordService->getUsersWithExpiringPasswords(7)->count();

        // Recent alerts
        $this->recentAlerts = SecurityAlert::unacknowledged()
            ->notExpired()
            ->orderBy('severity', 'desc')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // Top attacking IPs
        $this->topAttackingIPs = LoginAttempt::getSuspiciousIps(60, 5);

        // Locked out users
        $this->lockedOutUsers = User::whereNotNull('locked_until')
            ->where('locked_until', '>', now())
            ->orderBy('locked_until', 'desc')
            ->limit(10)
            ->get();

        // Users with expiring passwords
        $this->usersWithExpiringPasswords = $passwordService->getUsersWithExpiringPasswords(14);
    }

    // ==========================================
    // ALERT ACTIONS
    // ==========================================

    public function acknowledgeAlert(int $alertId): void
    {
        $alert = SecurityAlert::find($alertId);

        if ($alert) {
            $alert->acknowledge(auth()->id());
            session()->flash('success', 'Alert zostal potwierdzony.');
            $this->refreshStats();
        }
    }

    public function resolveAlert(int $alertId): void
    {
        $alert = SecurityAlert::find($alertId);

        if ($alert) {
            $alert->resolve(auth()->id());
            session()->flash('success', 'Alert zostal rozwiazany.');
            $this->refreshStats();
        }
    }

    public function dismissAlert(int $alertId): void
    {
        $this->acknowledgeAlert($alertId);
    }

    // ==========================================
    // USER ACTIONS
    // ==========================================

    public function unlockUser(int $userId): void
    {
        $user = User::find($userId);

        if ($user) {
            $passwordService = app(PasswordPolicyService::class);
            $passwordService->unlockAccount($user);

            session()->flash('success', "Konto uzytkownika {$user->full_name} zostalo odblokowane.");
            $this->refreshStats();
        }
    }

    public function forcePasswordChange(int $userId): void
    {
        $user = User::find($userId);

        if ($user) {
            $passwordService = app(PasswordPolicyService::class);
            $passwordService->requirePasswordChange($user);

            session()->flash('success', "Wymuszono zmiane hasla dla {$user->full_name}.");
            $this->refreshStats();
        }
    }

    public function terminateAllUserSessions(int $userId): void
    {
        $user = User::find($userId);

        if ($user) {
            $sessionService = app(SessionManagementService::class);
            $count = $sessionService->terminateAllUserSessions($user);

            session()->flash('success', "Zakonczono {$count} sesji uzytkownika {$user->full_name}.");
            $this->refreshStats();
        }
    }

    // ==========================================
    // IP ACTIONS
    // ==========================================

    public function openBlockIpModal(string $ip = ''): void
    {
        $this->blockIpAddress = $ip;
        $this->blockIpReason = '';
        $this->blockIpExpiry = '';
        $this->showBlockIpModal = true;
    }

    public function blockIp(): void
    {
        $this->validate([
            'blockIpAddress' => 'required|ip',
            'blockIpReason' => 'nullable|string|max:500',
            'blockIpExpiry' => 'nullable|date|after:now',
        ]);

        $blockedIp = BlockedIp::create([
            'ip_address' => $this->blockIpAddress,
            'reason' => $this->blockIpReason ?: null,
            'blocked_by' => auth()->id(),
            'expires_at' => $this->blockIpExpiry ?: null,
            'is_active' => true,
        ]);

        AuditLog::logCreated($blockedIp, 'Zablokowano adres IP: ' . $this->blockIpAddress);

        session()->flash('success', 'Adres IP ' . $this->blockIpAddress . ' zostal zablokowany.');

        $this->showBlockIpModal = false;
        $this->blockIpAddress = '';
        $this->blockIpReason = '';
        $this->blockIpExpiry = '';
        $this->refreshStats();
    }

    public function unblockIp(int $id): void
    {
        $blockedIp = BlockedIp::findOrFail($id);
        $oldValues = $blockedIp->toArray();
        $blockedIp->update(['is_active' => false]);

        AuditLog::logUpdated($blockedIp, $oldValues, 'Odblokowano adres IP: ' . $blockedIp->ip_address);

        session()->flash('success', 'Adres IP ' . $blockedIp->ip_address . ' zostal odblokowany.');
        $this->refreshStats();
    }

    // ==========================================
    // BULK ACTIONS
    // ==========================================

    public function acknowledgeAllAlerts(): void
    {
        SecurityAlert::unacknowledged()
            ->notExpired()
            ->update([
                'acknowledged' => true,
                'acknowledged_by' => auth()->id(),
                'acknowledged_at' => now(),
            ]);

        session()->flash('success', 'Wszystkie alerty zostaly potwierdzone.');
        $this->refreshStats();
    }

    public function unlockAllUsers(): void
    {
        User::whereNotNull('locked_until')
            ->where('locked_until', '>', now())
            ->update([
                'locked_until' => null,
                'failed_login_attempts' => 0,
            ]);

        session()->flash('success', 'Wszystkie zablokowane konta zostaly odblokowane.');
        $this->refreshStats();
    }

    // ==========================================
    // COMPUTED PROPERTIES
    // ==========================================

    public function getAlertsProperty()
    {
        $query = SecurityAlert::with(['relatedUser'])
            ->orderBy('created_at', 'desc');

        if ($this->alertFilter === 'unacknowledged') {
            $query->unacknowledged();
        } elseif ($this->alertFilter === 'critical') {
            $query->critical();
        }

        return $query->paginate($this->alertsPerPage);
    }

    public function getLoginAttemptsProperty()
    {
        return LoginAttempt::with('user')
            ->orderBy('attempted_at', 'desc')
            ->limit(50)
            ->get();
    }

    public function getPasswordPoliciesProperty()
    {
        return PasswordPolicy::active()
            ->withCount('users')
            ->orderBy('is_default', 'desc')
            ->get();
    }

    public function getBlockedIpsProperty()
    {
        return BlockedIp::with('blocker')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    // ==========================================
    // CHART DATA
    // ==========================================

    public function getFailedLoginsChartData(): array
    {
        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $count = LoginAttempt::failed()
                ->whereDate('attempted_at', $date)
                ->count();

            $data[] = [
                'date' => $date->format('d.m'),
                'count' => $count,
            ];
        }
        return $data;
    }

    public function getSessionsChartData(): array
    {
        $data = [];
        for ($i = 23; $i >= 0; $i--) {
            $hour = now()->subHours($i);
            $count = UserSession::where('created_at', '>=', $hour->copy()->startOfHour())
                ->where('created_at', '<', $hour->copy()->endOfHour())
                ->count();

            $data[] = [
                'hour' => $hour->format('H:00'),
                'count' => $count,
            ];
        }
        return $data;
    }

    // ==========================================
    // RENDER
    // ==========================================

    public function render()
    {
        return view('livewire.admin.security.security-dashboard', [
            'alerts' => $this->alerts,
            'loginAttempts' => $this->loginAttempts,
            'passwordPolicies' => $this->passwordPolicies,
            'blockedIps' => $this->blockedIps,
            'failedLoginsChartData' => $this->getFailedLoginsChartData(),
            'sessionsChartData' => $this->getSessionsChartData(),
        ])->layout('layouts.admin', [
            'title' => 'Dashboard Bezpieczenstwa - Admin PPM',
            'breadcrumb' => 'Bezpieczenstwo'
        ]);
    }
}
