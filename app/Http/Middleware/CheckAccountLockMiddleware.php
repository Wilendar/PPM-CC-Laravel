<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\User\PasswordPolicyService;
use Symfony\Component\HttpFoundation\Response;

/**
 * ETAP_04 FAZA A: Check Account Lock Middleware
 *
 * Prevents locked accounts from accessing the application.
 */
class CheckAccountLockMiddleware
{
    public function __construct(
        protected PasswordPolicyService $passwordService
    ) {}

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check()) {
            return $next($request);
        }

        $user = Auth::user();

        // Check if account is locked
        if ($this->passwordService->isAccountLocked($user)) {
            $minutesRemaining = $this->passwordService->getLockoutMinutesRemaining($user);

            // Log out the user
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            $message = "Twoje konto zostalo tymczasowo zablokowane. Sprobuj ponownie za {$minutesRemaining} minut.";

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $message,
                    'locked_until' => $user->locked_until,
                    'minutes_remaining' => $minutesRemaining,
                ], 423); // 423 Locked
            }

            return redirect()->route('login')
                ->with('error', $message);
        }

        // Check if user account is inactive
        if (!$user->is_active) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            $message = 'Twoje konto zostalo dezaktywowane. Skontaktuj sie z administratorem.';

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $message,
                    'account_inactive' => true,
                ], 403);
            }

            return redirect()->route('login')
                ->with('error', $message);
        }

        return $next($request);
    }
}
