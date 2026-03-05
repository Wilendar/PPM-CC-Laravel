<?php

namespace App\Http\Livewire\Profile;

use App\Models\UserSession;
use App\Services\User\SessionManagementService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Profile: User Sessions Management
 *
 * Displays user's active and historical sessions with ability
 * to terminate individual sessions or all other sessions.
 */
class UserSessions extends Component
{
    use WithPagination;

    public int $perPage = 15;

    // ==========================================
    // COMPUTED PROPERTIES
    // ==========================================

    /**
     * Get paginated sessions for the authenticated user.
     */
    #[Computed]
    public function sessions()
    {
        return UserSession::forUser(Auth::id())
            ->orderBy('is_active', 'desc')
            ->orderBy('last_activity', 'desc')
            ->paginate($this->perPage);
    }

    /**
     * Get count of active sessions.
     */
    #[Computed]
    public function activeSessionCount(): int
    {
        return UserSession::forUser(Auth::id())
            ->active()
            ->count();
    }

    // ==========================================
    // ACTIONS
    // ==========================================

    /**
     * Terminate a specific session.
     */
    public function terminateSession(int $sessionId): void
    {
        $session = UserSession::find($sessionId);

        if (!$session) {
            session()->flash('error', 'Sesja nie zostala znaleziona.');
            return;
        }

        // Validate session belongs to current user
        if ($session->user_id !== Auth::id()) {
            session()->flash('error', 'Brak uprawnien do zakonczenia tej sesji.');
            return;
        }

        // Prevent terminating current session
        if ($session->isCurrentSession()) {
            session()->flash('error', 'Nie mozesz zakonczyc biezacej sesji z tego panelu.');
            return;
        }

        // Prevent terminating already inactive session
        if (!$session->is_active) {
            session()->flash('info', 'Ta sesja jest juz nieaktywna.');
            return;
        }

        try {
            $service = app(SessionManagementService::class);
            $service->terminateSession($session, UserSession::END_FORCE_ADMIN);

            session()->flash('success', 'Sesja zostala zakonczona pomyslnie.');

            \Log::info('User terminated own session', [
                'user_id' => Auth::id(),
                'terminated_session_id' => $session->id,
                'terminated_session_ip' => $session->ip_address,
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to terminate session', [
                'user_id' => Auth::id(),
                'session_id' => $sessionId,
                'error' => $e->getMessage(),
            ]);

            session()->flash('error', 'Wystapil blad podczas konczenia sesji.');
        }
    }

    /**
     * Terminate all other sessions (except current).
     */
    public function terminateAllOtherSessions(): void
    {
        try {
            $service = app(SessionManagementService::class);
            $count = $service->terminateAllUserSessions(
                Auth::user(),
                session()->getId()
            );

            if ($count > 0) {
                session()->flash('success', "Zakonczono {$count} sesji na innych urzadzeniach.");

                \Log::info('User terminated all other sessions', [
                    'user_id' => Auth::id(),
                    'terminated_count' => $count,
                ]);
            } else {
                session()->flash('info', 'Brak innych aktywnych sesji do zakonczenia.');
            }
        } catch (\Exception $e) {
            \Log::error('Failed to terminate all other sessions', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);

            session()->flash('error', 'Wystapil blad podczas konczenia sesji.');
        }
    }

    // ==========================================
    // HELPERS
    // ==========================================

    /**
     * Get badge color classes based on status badge color.
     */
    public function getStatusColorClasses(string $color): string
    {
        return match ($color) {
            'green' => 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30',
            'yellow' => 'bg-amber-500/20 text-amber-400 border border-amber-500/30',
            'red' => 'bg-red-500/20 text-red-400 border border-red-500/30',
            'gray' => 'bg-gray-500/20 text-gray-400 border border-gray-500/30',
            default => 'bg-gray-500/20 text-gray-400 border border-gray-500/30',
        };
    }

    // ==========================================
    // RENDER
    // ==========================================

    public function render()
    {
        return view('livewire.profile.user-sessions')
            ->layout('layouts.admin', [
                'title' => 'Aktywne sesje - Admin PPM',
                'breadcrumb' => 'Aktywne sesje',
            ]);
    }
}
