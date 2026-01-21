<?php

namespace App\Services\User;

use App\Models\User;
use App\Models\UserSession;
use App\Models\SecurityAlert;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Jenssegers\Agent\Agent;

/**
 * ETAP_04 FAZA A: Session Management Service
 *
 * Handles creation, termination, and monitoring of user sessions.
 */
class SessionManagementService
{
    protected Agent $agent;

    public function __construct()
    {
        $this->agent = new Agent();
    }

    /**
     * Create a new session record for a user.
     */
    public function createSession(User $user, Request $request): UserSession
    {
        $this->agent->setUserAgent($request->userAgent());

        // Detect device info
        $deviceType = $this->detectDeviceType();
        $browser = $this->agent->browser() ?: null;
        $browserVersion = $this->agent->version($browser) ?: null;
        $os = $this->agent->platform() ?: null;
        $osVersion = $this->agent->version($os) ?: null;

        // Get geolocation from IP (placeholder - implement with GeoIP service)
        $geoData = $this->getGeoLocation($request->ip());

        // Check concurrent session limit
        $this->enforceSessionLimit($user);

        // Create the session record
        $session = UserSession::create([
            'user_id' => $user->id,
            'session_id' => session()->getId(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'device_type' => $deviceType,
            'browser' => $browser,
            'browser_version' => $browserVersion,
            'os' => $os,
            'os_version' => $osVersion,
            'country' => $geoData['country'] ?? null,
            'country_code' => $geoData['country_code'] ?? null,
            'city' => $geoData['city'] ?? null,
            'region' => $geoData['region'] ?? null,
            'is_active' => true,
            'last_activity' => now(),
        ]);

        // Update user's last login info
        $user->update([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
            'failed_login_attempts' => 0,
        ]);

        // Check for suspicious activity
        $this->checkForSuspiciousActivity($user, $session);

        return $session;
    }

    /**
     * Terminate a specific session.
     */
    public function terminateSession(UserSession $session, string $reason): void
    {
        $session->terminate($reason);

        // Also invalidate the Laravel session
        $this->invalidateLaravelSession($session->session_id);
    }

    /**
     * Terminate all sessions for a user.
     *
     * @param User $user
     * @param string|null $exceptSessionId Session ID to keep active
     * @return int Number of terminated sessions
     */
    public function terminateAllUserSessions(User $user, ?string $exceptSessionId = null): int
    {
        $query = UserSession::forUser($user->id)->active();

        if ($exceptSessionId) {
            $query->where('session_id', '!=', $exceptSessionId);
        }

        $sessions = $query->get();
        $count = 0;

        foreach ($sessions as $session) {
            $this->terminateSession($session, UserSession::END_FORCE_ADMIN);
            $count++;
        }

        return $count;
    }

    /**
     * Terminate all sessions except admin users.
     *
     * @return int Number of terminated sessions
     */
    public function terminateAllSessionsExceptAdmin(): int
    {
        $adminUserIds = User::role('Admin')->pluck('id');

        $sessions = UserSession::active()
            ->whereNotIn('user_id', $adminUserIds)
            ->get();

        $count = 0;
        foreach ($sessions as $session) {
            $this->terminateSession($session, UserSession::END_FORCE_ADMIN);
            $count++;
        }

        return $count;
    }

    /**
     * Get all active sessions.
     */
    public function getActiveSessions(): Collection
    {
        return UserSession::active()
            ->with('user')
            ->orderBy('last_activity', 'desc')
            ->get();
    }

    /**
     * Get sessions for a specific user.
     */
    public function getUserSessions(User $user): Collection
    {
        return UserSession::forUser($user->id)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get active sessions for a specific user.
     */
    public function getUserActiveSessions(User $user): Collection
    {
        return UserSession::forUser($user->id)
            ->active()
            ->orderBy('last_activity', 'desc')
            ->get();
    }

    /**
     * Detect suspicious sessions.
     */
    public function detectSuspiciousSessions(): Collection
    {
        $suspicious = collect();

        // Sessions from multiple countries for same user
        $multiCountryUsers = UserSession::active()
            ->whereNotNull('country')
            ->select('user_id')
            ->selectRaw('COUNT(DISTINCT country) as country_count')
            ->groupBy('user_id')
            ->having('country_count', '>', 2)
            ->pluck('user_id');

        if ($multiCountryUsers->isNotEmpty()) {
            $suspicious = $suspicious->merge(
                UserSession::active()
                    ->whereIn('user_id', $multiCountryUsers)
                    ->get()
            );
        }

        // Sessions from IPs used by multiple users
        $suspiciousIps = UserSession::active()
            ->select('ip_address')
            ->selectRaw('COUNT(DISTINCT user_id) as user_count')
            ->groupBy('ip_address')
            ->having('user_count', '>', 3)
            ->pluck('ip_address');

        if ($suspiciousIps->isNotEmpty()) {
            $suspicious = $suspicious->merge(
                UserSession::active()
                    ->whereIn('ip_address', $suspiciousIps)
                    ->get()
            );
        }

        return $suspicious->unique('id');
    }

    /**
     * Update session activity timestamp.
     */
    public function updateSessionActivity(string $sessionId, ?string $url = null): void
    {
        $session = UserSession::where('session_id', $sessionId)->first();

        if ($session && $session->is_active) {
            $session->touchActivity($url);
        }
    }

    /**
     * Find session by Laravel session ID.
     */
    public function findBySessionId(string $sessionId): ?UserSession
    {
        return UserSession::where('session_id', $sessionId)->first();
    }

    /**
     * Get session statistics.
     */
    public function getSessionStats(): array
    {
        $total = UserSession::count();
        $active = UserSession::active()->count();
        $today = UserSession::whereDate('created_at', today())->count();
        $suspicious = UserSession::suspicious()->count();

        return [
            'total' => $total,
            'active' => $active,
            'today' => $today,
            'suspicious' => $suspicious,
            'peak_concurrent' => $this->calculatePeakConcurrent(),
            'avg_duration_minutes' => $this->calculateAverageDuration(),
        ];
    }

    /**
     * Get device type statistics.
     */
    public function getDeviceStats(): array
    {
        return UserSession::select('device_type', DB::raw('COUNT(*) as count'))
            ->groupBy('device_type')
            ->orderBy('count', 'desc')
            ->pluck('count', 'device_type')
            ->toArray();
    }

    /**
     * Clean up old inactive sessions.
     */
    public function cleanupOldSessions(int $daysToKeep = 30): int
    {
        return UserSession::where('is_active', false)
            ->where('ended_at', '<', now()->subDays($daysToKeep))
            ->delete();
    }

    /**
     * Mark sessions as expired based on inactivity.
     */
    public function expireInactiveSessions(int $inactivityMinutes = 120): int
    {
        $expiredSessions = UserSession::active()
            ->where('last_activity', '<', now()->subMinutes($inactivityMinutes))
            ->get();

        $count = 0;
        foreach ($expiredSessions as $session) {
            $session->terminate(UserSession::END_TIMEOUT);
            $this->invalidateLaravelSession($session->session_id);
            $count++;
        }

        return $count;
    }

    // ==========================================
    // PROTECTED METHODS
    // ==========================================

    /**
     * Detect device type from user agent.
     */
    protected function detectDeviceType(): string
    {
        if ($this->agent->isTablet()) {
            return UserSession::DEVICE_TABLET;
        }

        if ($this->agent->isMobile()) {
            return UserSession::DEVICE_MOBILE;
        }

        if ($this->agent->isDesktop()) {
            return UserSession::DEVICE_DESKTOP;
        }

        return UserSession::DEVICE_UNKNOWN;
    }

    /**
     * Get geolocation from IP address.
     * Placeholder - implement with actual GeoIP service.
     */
    protected function getGeoLocation(string $ip): array
    {
        // TODO: Implement with MaxMind GeoIP or similar service
        // For now, return empty data
        return [
            'country' => null,
            'country_code' => null,
            'city' => null,
            'region' => null,
        ];
    }

    /**
     * Enforce concurrent session limit for user.
     */
    protected function enforceSessionLimit(User $user): void
    {
        $maxSessions = $user->max_concurrent_sessions ?? 3;
        $activeSessions = UserSession::forUser($user->id)
            ->active()
            ->orderBy('last_activity', 'asc')
            ->get();

        // If at or over limit, terminate oldest sessions
        while ($activeSessions->count() >= $maxSessions) {
            $oldestSession = $activeSessions->shift();
            $this->terminateSession($oldestSession, UserSession::END_CONCURRENT);
        }
    }

    /**
     * Check for suspicious activity patterns.
     */
    protected function checkForSuspiciousActivity(User $user, UserSession $newSession): void
    {
        // Check for multiple countries
        $countries = UserSession::forUser($user->id)
            ->active()
            ->whereNotNull('country')
            ->distinct()
            ->pluck('country');

        if ($countries->count() > 2) {
            $newSession->markSuspicious('Logowanie z wielu krajow jednoczesnie');

            SecurityAlert::createAlert(
                SecurityAlert::TYPE_UNUSUAL_LOCATION,
                SecurityAlert::SEVERITY_MEDIUM,
                'Logowanie z nietypowej lokalizacji',
                "Uzytkownik {$user->full_name} ma sesje z {$countries->count()} roznych krajow.",
                ['countries' => $countries->toArray()],
                $user->id,
                $newSession->ip_address,
                $newSession->id
            );
        }
    }

    /**
     * Invalidate Laravel session from database.
     */
    protected function invalidateLaravelSession(string $sessionId): void
    {
        DB::table('sessions')->where('id', $sessionId)->delete();
    }

    /**
     * Calculate peak concurrent sessions.
     */
    protected function calculatePeakConcurrent(): int
    {
        // Simplified: return max active sessions in any single hour in last 7 days
        $result = UserSession::selectRaw('DATE_FORMAT(created_at, "%Y-%m-%d %H:00:00") as hour, COUNT(*) as count')
            ->where('created_at', '>=', now()->subDays(7))
            ->groupBy('hour')
            ->orderBy('count', 'desc')
            ->first();

        return $result->count ?? 0;
    }

    /**
     * Calculate average session duration in minutes.
     */
    protected function calculateAverageDuration(): int
    {
        $completedSessions = UserSession::whereNotNull('ended_at')
            ->where('created_at', '>=', now()->subDays(30))
            ->get();

        if ($completedSessions->isEmpty()) {
            return 0;
        }

        $totalMinutes = $completedSessions->sum(function ($session) {
            return $session->created_at->diffInMinutes($session->ended_at);
        });

        return (int) round($totalMinutes / $completedSessions->count());
    }
}
