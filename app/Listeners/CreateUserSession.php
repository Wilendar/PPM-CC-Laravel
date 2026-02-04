<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Login;
use App\Services\User\SessionManagementService;
use App\Models\UserSession;

class CreateUserSession
{
    /**
     * Create the event listener.
     */
    public function __construct(
        protected SessionManagementService $sessionService
    ) {}

    /**
     * Handle the event - create user session record on login.
     *
     * IMPORTANT: Sprawdza czy sesja już istnieje przed utworzeniem nowej.
     * Zapobiega duplikowaniu sesji przy wielokrotnych wywołaniach Auth::login().
     */
    public function handle(Login $event): void
    {
        $sessionId = session()->getId();

        // Sprawdź czy sesja już istnieje dla tego session_id i użytkownika
        $existingSession = UserSession::where('session_id', $sessionId)
            ->where('user_id', $event->user->id)
            ->where('is_active', true)
            ->first();

        // Jeśli sesja istnieje - tylko zaktualizuj last_activity
        if ($existingSession) {
            $existingSession->update(['last_activity' => now()]);
            return;
        }

        // Twórz nową sesję tylko jeśli nie istnieje
        $this->sessionService->createSession(
            $event->user,
            request()
        );
    }
}
