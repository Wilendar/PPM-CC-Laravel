<?php

namespace App\Http\Livewire\Dashboard\Widgets;

use Livewire\Component;
use App\Models\LoginAttempt;
use App\Models\UserSession;
use Illuminate\Support\Facades\Auth;

class WelcomeCard extends Component
{
    public string $userName = '';
    public string $userEmail = '';
    public string $userRole = '';
    public ?string $lastLoginAt = null;
    public ?string $lastLoginIp = null;
    public int $sessionDuration = 0;

    public function mount(): void
    {
        $user = Auth::user();

        if (!$user) {
            return;
        }

        $this->userName = $user->full_name ?: $user->name;
        $this->userEmail = $user->email;
        $this->userRole = $user->getRoleNames()->first() ?? 'User';

        $this->loadLastLogin($user->id);
        $this->loadSessionDuration($user->id);
    }

    protected function loadLastLogin(int $userId): void
    {
        $lastLogin = LoginAttempt::where('user_id', $userId)
            ->successful()
            ->latest('attempted_at')
            ->first();

        if ($lastLogin) {
            $this->lastLoginAt = $lastLogin->attempted_at->diffForHumans();
            $this->lastLoginIp = $lastLogin->ip_address;
            return;
        }

        // Fallback: use most recent ended UserSession
        $lastSession = UserSession::forUser($userId)
            ->where('is_active', false)
            ->latest('created_at')
            ->first();

        if ($lastSession) {
            $this->lastLoginAt = $lastSession->created_at->diffForHumans();
            $this->lastLoginIp = $lastSession->ip_address;
        }
    }

    protected function loadSessionDuration(int $userId): void
    {
        $sessions = UserSession::forUser($userId)
            ->whereDate('created_at', today())
            ->orderBy('created_at')
            ->get();

        if ($sessions->isEmpty()) {
            $this->sessionDuration = 0;
            return;
        }

        // Merge overlapping intervals to avoid double-counting
        $intervals = $sessions->map(fn ($s) => [
            'start' => $s->created_at->timestamp,
            'end' => ($s->ended_at ?? now())->timestamp,
        ])->sortBy('start')->values()->toArray();

        $merged = [$intervals[0]];

        for ($i = 1; $i < count($intervals); $i++) {
            $last = &$merged[count($merged) - 1];
            if ($intervals[$i]['start'] <= $last['end']) {
                $last['end'] = max($last['end'], $intervals[$i]['end']);
            } else {
                $merged[] = $intervals[$i];
            }
        }

        $totalSeconds = array_sum(array_map(
            fn ($iv) => $iv['end'] - $iv['start'],
            $merged
        ));

        $this->sessionDuration = (int) round($totalSeconds / 60);
    }

    protected function getGreeting(): string
    {
        $hour = (int) now()->format('H');

        if ($hour < 6) {
            return "Ka\u{017C}da pora jest dobra na prac\u{0119}, nawet noc";
        } elseif ($hour < 12) {
            return "Dzie\u{0144} dobry";
        } elseif ($hour < 18) {
            return "Dobrego popo\u{0142}udnia";
        }

        return "Dobry wiecz\u{00F3}r";
    }

    protected function getFormattedDuration(): string
    {
        if ($this->sessionDuration < 1) {
            return "Przed chwil\u{0105}";
        }

        $hours = intdiv($this->sessionDuration, 60);
        $minutes = $this->sessionDuration % 60;

        if ($hours > 0) {
            return "{$hours}h {$minutes}min";
        }

        return "{$minutes} min";
    }

    public function render()
    {
        return view('livewire.dashboard.widgets.welcome-card', [
            'greeting' => $this->getGreeting(),
            'formattedDuration' => $this->getFormattedDuration(),
        ]);
    }
}
