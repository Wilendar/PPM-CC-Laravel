<?php

namespace App\Http\Livewire\Dashboard\Widgets;

use Livewire\Component;
use App\Models\LoginAttempt;
use App\Models\UserSession;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class LoginHistory extends Component
{
    public Collection $logins;
    public Collection $sessions;
    public bool $usingSessionFallback = false;

    public function mount(): void
    {
        $userId = Auth::id();
        $this->sessions = collect();

        $this->logins = $userId
            ? LoginAttempt::where('user_id', $userId)
                ->orderBy('attempted_at', 'desc')
                ->limit(10)
                ->get()
            : collect();

        // Fallback: use UserSession if no LoginAttempts
        if ($this->logins->isEmpty() && $userId) {
            $this->sessions = UserSession::forUser($userId)
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get();
            $this->usingSessionFallback = true;
        }
    }

    /**
     * Get device type icon SVG path.
     */
    public function getDeviceIconPath(string $deviceType): string
    {
        return match ($deviceType) {
            'mobile' => 'M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z',
            'tablet' => 'M12 18h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z',
            default => 'M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z',
        };
    }

    /**
     * Format location from country + city.
     */
    public function formatLocation(LoginAttempt $login): string
    {
        $parts = array_filter([
            $login->city,
            $login->country,
        ]);

        return implode(', ', $parts) ?: '-';
    }

    public function render()
    {
        return view('livewire.dashboard.widgets.login-history');
    }
}
