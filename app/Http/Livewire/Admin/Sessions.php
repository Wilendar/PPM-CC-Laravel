<?php

namespace App\Http\Livewire\Admin;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\UserSession;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Collection;
use Jenssegers\Agent\Agent;

/**
 * Session Management UI Component
 * 
 * FAZA C: System-wide session monitoring
 * 
 * Features:
 * - Active sessions overview z device/location details
 * - Session analytics (peak usage, device types)
 * - Force logout capabilities dla individual sessions
 * - Session security alerts
 * - Multiple session detection per user
 * - Device fingerprinting display
 * - Suspicious login pattern detection
 * - IP geolocation mapping
 */
class Sessions extends Component
{
    use WithPagination;

    // ==========================================
    // CORE PROPERTIES
    // ==========================================

    public $viewMode = 'active'; // active, analytics, security
    public $selectedSession = null;
    public $showSessionDetails = false;
    
    // ==========================================
    // FILTERING & SEARCH
    // ==========================================
    
    public $search = '';
    public $userFilter = 'all';
    public $deviceFilter = 'all';
    public $statusFilter = 'active';
    public $locationFilter = '';
    public $sortField = 'last_activity';
    public $sortDirection = 'desc';
    public $perPage = 25;
    
    // ==========================================
    // BULK OPERATIONS
    // ==========================================
    
    public $selectedSessions = [];
    public $selectAll = false;
    public $showBulkModal = false;
    public $bulkAction = '';
    
    // ==========================================
    // SECURITY MONITORING
    // ==========================================
    
    public $securityAlerts = [];
    public $suspiciousPatterns = [];
    public $multipleSessionUsers = [];
    public $unusualLocations = [];
    
    // ==========================================
    // ANALYTICS DATA
    // ==========================================
    
    public $sessionStats = [];
    public $deviceStats = [];
    public $locationStats = [];
    public $timelineData = [];
    public $peakUsageData = [];

    // ==========================================
    // COMPONENT LIFECYCLE
    // ==========================================

    public function mount()
    {
        $this->authorize('viewAny', UserSession::class);
        
        $this->calculateSessionStats();
        $this->detectSecurityIssues();
        $this->calculateAnalytics();
    }

    // ==========================================
    // VIEW MODE MANAGEMENT
    // ==========================================

    public function setViewMode($mode)
    {
        $this->viewMode = $mode;
        
        if ($mode === 'analytics') {
            $this->calculateAnalytics();
        } elseif ($mode === 'security') {
            $this->detectSecurityIssues();
        }
    }

    // ==========================================
    // FILTERING & SEARCH
    // ==========================================

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedUserFilter()
    {
        $this->resetPage();
    }

    public function updatedDeviceFilter()
    {
        $this->resetPage();
    }

    public function updatedStatusFilter()
    {
        $this->resetPage();
    }

    public function clearFilters()
    {
        $this->search = '';
        $this->userFilter = 'all';
        $this->deviceFilter = 'all';
        $this->statusFilter = 'active';
        $this->locationFilter = '';
        $this->resetPage();
    }

    // ==========================================
    // SORTING
    // ==========================================

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'desc';
        }

        $this->resetPage();
    }

    // ==========================================
    // SESSION DETAILS
    // ==========================================

    public function showSessionDetails($sessionId)
    {
        $this->selectedSession = UserSession::with(['user', 'auditLogs'])
            ->findOrFail($sessionId);
        $this->showSessionDetails = true;
    }

    public function closeSessionDetails()
    {
        $this->showSessionDetails = false;
        $this->selectedSession = null;
    }

    // ==========================================
    // SESSION ACTIONS
    // ==========================================

    public function forceLogout($sessionId)
    {
        $this->authorize('forceLogout', UserSession::class);
        
        $session = UserSession::findOrFail($sessionId);
        
        // Don't allow logout of current session
        if ($session->session_id === session()->getId()) {
            session()->flash('error', 'Nie możesz wylogować swojej aktualnej sesji.');
            return;
        }
        
        // Mark session as terminated
        $session->update([
            'is_active' => false,
            'ended_at' => now(),
            'end_reason' => 'force_logout_admin'
        ]);
        
        // Invalidate Laravel session if it exists
        $this->invalidateLaravelSession($session->session_id);
        
        // Log the action
        activity()
            ->performedOn($session)
            ->causedBy(auth()->user())
            ->withProperties([
                'target_user' => $session->user->full_name,
                'session_id' => $session->session_id,
                'ip_address' => $session->ip_address
            ])
            ->log('force_logout');
        
        session()->flash('success', "Sesja użytkownika {$session->user->full_name} została zakończona.");
        
        $this->calculateSessionStats();
    }

    public function blockUserSessions($userId)
    {
        $this->authorize('blockUser', User::class);
        
        $user = User::findOrFail($userId);
        $activeSessions = UserSession::where('user_id', $userId)
            ->where('is_active', true)
            ->get();
        
        foreach ($activeSessions as $session) {
            // Skip current session
            if ($session->session_id === session()->getId()) {
                continue;
            }
            
            $session->update([
                'is_active' => false,
                'ended_at' => now(),
                'end_reason' => 'user_blocked'
            ]);
            
            $this->invalidateLaravelSession($session->session_id);
        }
        
        // Temporarily block user (this would require additional user table field)
        // $user->update(['session_blocked_until' => now()->addHours(24)]);
        
        activity()
            ->performedOn($user)
            ->causedBy(auth()->user())
            ->withProperties([
                'terminated_sessions' => $activeSessions->count(),
                'reason' => 'security_block'
            ])
            ->log('block_user_sessions');
        
        session()->flash('success', "Zablokowano {$activeSessions->count()} sesji użytkownika {$user->full_name}.");
        
        $this->calculateSessionStats();
    }

    protected function invalidateLaravelSession($sessionId)
    {
        // This would require custom session driver or Redis cleanup
        // For now, we'll just mark as inactive in our tracking
        DB::table('sessions')->where('id', $sessionId)->delete();
    }

    /**
     * Force logout all sessions except administrators.
     */
    public function forceLogoutAllExceptAdmin()
    {
        $this->authorize('forceLogoutAll', UserSession::class);

        $adminUserIds = User::role('Admin')->pluck('id');

        $count = UserSession::where('is_active', true)
            ->whereNotIn('user_id', $adminUserIds)
            ->update([
                'is_active' => false,
                'ended_at' => now(),
                'end_reason' => 'bulk_force_logout_admin'
            ]);

        $this->refreshStats();
        session()->flash('success', "Wylogowano {$count} sesji (oprocz administratorow).");
    }

    /**
     * Refresh session statistics.
     */
    protected function refreshStats()
    {
        $this->calculateSessionStats();
        $this->detectSecurityIssues();
    }

    // ==========================================
    // BULK OPERATIONS
    // ==========================================

    public function updatedSelectAll($value)
    {
        if ($value) {
            $this->selectedSessions = $this->sessions->pluck('id')->toArray();
        } else {
            $this->selectedSessions = [];
        }
    }

    public function updatedSelectedSessions()
    {
        $this->selectAll = count($this->selectedSessions) === $this->sessions->count();
    }

    public function openBulkModal()
    {
        if (empty($this->selectedSessions)) {
            session()->flash('error', 'Wybierz przynajmniej jedną sesję.');
            return;
        }

        $this->showBulkModal = true;
    }

    public function closeBulkModal()
    {
        $this->showBulkModal = false;
        $this->bulkAction = '';
    }

    public function executeBulkAction()
    {
        $this->authorize('bulkActions', UserSession::class);
        
        if (empty($this->selectedSessions)) {
            session()->flash('error', 'Brak wybranych sesji.');
            return;
        }

        switch ($this->bulkAction) {
            case 'force_logout':
                $this->bulkForceLogout();
                break;
            case 'mark_suspicious':
                $this->bulkMarkSuspicious();
                break;
        }

        $this->closeBulkModal();
        $this->selectedSessions = [];
        $this->selectAll = false;
        $this->calculateSessionStats();
    }

    protected function bulkForceLogout()
    {
        $currentSessionId = session()->getId();
        $sessions = UserSession::whereIn('id', $this->selectedSessions)
            ->where('session_id', '!=', $currentSessionId)
            ->where('is_active', true)
            ->get();

        $count = 0;
        foreach ($sessions as $session) {
            $session->update([
                'is_active' => false,
                'ended_at' => now(),
                'end_reason' => 'bulk_force_logout'
            ]);
            
            $this->invalidateLaravelSession($session->session_id);
            $count++;
        }

        activity()
            ->causedBy(auth()->user())
            ->withProperties([
                'terminated_sessions' => $count,
                'session_ids' => $sessions->pluck('session_id')->toArray()
            ])
            ->log('bulk_force_logout');

        session()->flash('success', "Zakończono {$count} sesji.");
    }

    protected function bulkMarkSuspicious()
    {
        UserSession::whereIn('id', $this->selectedSessions)
            ->update(['is_suspicious' => true]);

        activity()
            ->causedBy(auth()->user())
            ->withProperties([
                'marked_sessions' => count($this->selectedSessions)
            ])
            ->log('bulk_mark_suspicious');

        session()->flash('success', 'Oznaczono sesje jako podejrzane.');
    }

    // ==========================================
    // ANALYTICS CALCULATION
    // ==========================================

    protected function calculateSessionStats()
    {
        $totalSessions = UserSession::count();
        $activeSessions = UserSession::where('is_active', true)->count();
        $todaySessions = UserSession::whereDate('created_at', today())->count();
        $suspiciousSessions = UserSession::where('is_suspicious', true)->count();

        $this->sessionStats = [
            'total' => $totalSessions,
            'active' => $activeSessions,
            'today' => $todaySessions,
            'suspicious' => $suspiciousSessions,
            'avg_duration' => $this->calculateAverageSessionDuration(),
            'peak_concurrent' => $this->calculatePeakConcurrentSessions()
        ];
    }

    protected function calculateAnalytics()
    {
        // Device statistics
        $this->deviceStats = UserSession::select('device_type', DB::raw('count(*) as count'))
            ->groupBy('device_type')
            ->orderBy('count', 'desc')
            ->get()
            ->map(function ($item) {
                return [
                    'type' => $item->device_type ?: 'Unknown',
                    'count' => $item->count,
                    'percentage' => round(($item->count / $this->sessionStats['total']) * 100, 1)
                ];
            });

        // Location statistics (by country)
        $this->locationStats = UserSession::select('country', DB::raw('count(*) as count'))
            ->whereNotNull('country')
            ->groupBy('country')
            ->orderBy('count', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($item) {
                return [
                    'country' => $item->country,
                    'count' => $item->count,
                    'percentage' => round(($item->count / $this->sessionStats['total']) * 100, 1)
                ];
            });

        // Timeline data (sessions per hour for last 24h)
        $this->timelineData = $this->calculateHourlySessionData();
    }

    protected function calculateAverageSessionDuration()
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

        return round($totalMinutes / $completedSessions->count());
    }

    protected function calculatePeakConcurrentSessions()
    {
        // This would require more complex calculation based on session overlap
        // For now, return max active sessions in a single hour
        return UserSession::select(DB::raw('DATE_FORMAT(created_at, "%Y-%m-%d %H:00:00") as hour'), DB::raw('count(*) as count'))
            ->where('created_at', '>=', now()->subDays(7))
            ->groupBy('hour')
            ->orderBy('count', 'desc')
            ->first()
            ->count ?? 0;
    }

    protected function calculateHourlySessionData()
    {
        $data = [];
        $startTime = now()->subHours(24);

        for ($i = 0; $i < 24; $i++) {
            $hour = $startTime->copy()->addHours($i);
            $count = UserSession::where('created_at', '>=', $hour)
                ->where('created_at', '<', $hour->copy()->addHour())
                ->count();

            $data[] = [
                'hour' => $hour->format('H:i'),
                'count' => $count,
                'timestamp' => $hour->timestamp
            ];
        }

        return $data;
    }

    // ==========================================
    // SECURITY DETECTION
    // ==========================================

    protected function detectSecurityIssues()
    {
        $this->securityAlerts = [];
        $this->multipleSessionUsers = [];
        $this->unusualLocations = [];

        // Detect users with multiple active sessions
        $multipleSessionUsers = UserSession::select('user_id', DB::raw('count(*) as session_count'))
            ->where('is_active', true)
            ->groupBy('user_id')
            ->having('session_count', '>', 3)
            ->with('user')
            ->get();

        foreach ($multipleSessionUsers as $userSessions) {
            $this->multipleSessionUsers[] = [
                'user' => User::find($userSessions->user_id),
                'session_count' => $userSessions->session_count,
                'severity' => $userSessions->session_count > 5 ? 'high' : 'medium'
            ];
        }

        // Detect sessions from unusual locations
        $userLocations = UserSession::where('is_active', true)
            ->whereNotNull('country')
            ->with('user')
            ->get()
            ->groupBy('user_id');

        foreach ($userLocations as $userId => $sessions) {
            $countries = $sessions->pluck('country')->unique();
            if ($countries->count() > 2) {
                $this->unusualLocations[] = [
                    'user' => User::find($userId),
                    'countries' => $countries->toArray(),
                    'session_count' => $sessions->count(),
                    'severity' => $countries->count() > 3 ? 'high' : 'medium'
                ];
            }
        }

        // Detect suspicious IP patterns
        $this->detectSuspiciousIPs();

        // Generate security alerts
        $this->generateSecurityAlerts();
    }

    protected function detectSuspiciousIPs()
    {
        // Detect IPs with multiple user sessions
        $suspiciousIPs = UserSession::select('ip_address', DB::raw('count(distinct user_id) as user_count'))
            ->where('created_at', '>=', now()->subHours(24))
            ->groupBy('ip_address')
            ->having('user_count', '>', 5)
            ->get();

        foreach ($suspiciousIPs as $ipData) {
            $this->securityAlerts[] = [
                'type' => 'suspicious_ip',
                'severity' => 'high',
                'message' => "IP {$ipData->ip_address} używane przez {$ipData->user_count} różnych użytkowników",
                'details' => [
                    'ip_address' => $ipData->ip_address,
                    'user_count' => $ipData->user_count
                ]
            ];
        }
    }

    protected function generateSecurityAlerts()
    {
        // Alert for multiple sessions
        if (count($this->multipleSessionUsers) > 0) {
            $highRiskUsers = collect($this->multipleSessionUsers)->where('severity', 'high')->count();
            if ($highRiskUsers > 0) {
                $this->securityAlerts[] = [
                    'type' => 'multiple_sessions',
                    'severity' => 'high',
                    'message' => "{$highRiskUsers} użytkowników ma więcej niż 5 aktywnych sesji",
                    'count' => $highRiskUsers
                ];
            }
        }

        // Alert for unusual locations
        if (count($this->unusualLocations) > 0) {
            $this->securityAlerts[] = [
                'type' => 'unusual_locations',
                'severity' => 'medium',
                'message' => count($this->unusualLocations) . " użytkowników loguje się z wielu krajów jednocześnie",
                'count' => count($this->unusualLocations)
            ];
        }
    }

    public function dismissSecurityAlert($index)
    {
        unset($this->securityAlerts[$index]);
        $this->securityAlerts = array_values($this->securityAlerts);
    }

    // ==========================================
    // DATA METHODS
    // ==========================================

    public function getSessionsProperty()
    {
        return $this->getSessionsQuery()->paginate($this->perPage);
    }

    protected function getSessionsQuery()
    {
        $query = UserSession::with(['user'])
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('ip_address', 'like', '%' . $this->search . '%')
                      ->orWhere('user_agent', 'like', '%' . $this->search . '%')
                      ->orWhere('city', 'like', '%' . $this->search . '%')
                      ->orWhere('country', 'like', '%' . $this->search . '%')
                      ->orWhereHas('user', function ($userQuery) {
                          $userQuery->where('first_name', 'like', '%' . $this->search . '%')
                                   ->orWhere('last_name', 'like', '%' . $this->search . '%')
                                   ->orWhere('email', 'like', '%' . $this->search . '%');
                      });
                });
            })
            ->when($this->userFilter !== 'all', function ($query) {
                $query->where('user_id', $this->userFilter);
            })
            ->when($this->deviceFilter !== 'all', function ($query) {
                $query->where('device_type', $this->deviceFilter);
            })
            ->when($this->statusFilter !== 'all', function ($query) {
                switch ($this->statusFilter) {
                    case 'active':
                        $query->where('is_active', true);
                        break;
                    case 'inactive':
                        $query->where('is_active', false);
                        break;
                    case 'suspicious':
                        $query->where('is_suspicious', true);
                        break;
                }
            })
            ->when($this->locationFilter, function ($query) {
                $query->where('country', 'like', '%' . $this->locationFilter . '%')
                      ->orWhere('city', 'like', '%' . $this->locationFilter . '%');
            })
            ->orderBy($this->sortField, $this->sortDirection);

        return $query;
    }

    public function getUsersProperty()
    {
        return User::orderBy('first_name')->get();
    }

    public function getDeviceTypesProperty()
    {
        return UserSession::select('device_type')
            ->distinct()
            ->whereNotNull('device_type')
            ->orderBy('device_type')
            ->pluck('device_type');
    }

    // ==========================================
    // UTILITY METHODS
    // ==========================================

    public function getDeviceIcon($deviceType)
    {
        switch (strtolower($deviceType)) {
            case 'desktop':
                return 'M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z';
            case 'mobile':
                return 'M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z';
            case 'tablet':
                return 'M12 18h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z';
            default:
                return 'M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z';
        }
    }

    public function formatDuration($minutes)
    {
        if ($minutes < 60) {
            return $minutes . ' min';
        }
        
        $hours = floor($minutes / 60);
        $remainingMinutes = $minutes % 60;
        
        return $hours . 'h ' . $remainingMinutes . 'min';
    }

    // ==========================================
    // RENDER METHOD
    // ==========================================

    public function render()
    {
        return view('livewire.admin.sessions', [
            'sessions' => $this->sessions,
            'users' => $this->users,
            'deviceTypes' => $this->deviceTypes,
        ])->layout('layouts.admin', [
            'title' => 'Monitor Sesji - Admin PPM',
            'breadcrumb' => 'Monitor Sesji'
        ]);
    }
}