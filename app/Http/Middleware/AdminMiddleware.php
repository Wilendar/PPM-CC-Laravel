<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AdminMiddleware
{
    /**
     * Handle an incoming request for admin routes.
     * Tylko uzytkownicy z rola Admin maja dostep do panelu.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Jesli niezalogowany, zwroc widok logowania (HTTP 200) zamiast 302
        if (!Auth::check()) {
            Log::warning('Unauthorized admin access attempt', [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'url' => $request->fullUrl(),
            ]);

            return response()->view('auth.login');
        }

        $user = Auth::user();

        // Wymagana rola Admin
        if (!$user->hasRole('Admin')) {
            Log::warning('Non-admin user attempted to access admin area', [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'user_roles' => $user->getRoleNames(),
                'ip' => $request->ip(),
                'url' => $request->fullUrl(),
            ]);

            abort(403, 'Dostep zabroniony. Wymagane uprawnienia administratora.');
        }

        // Konto musi byc aktywne
        if (!$user->is_active) {
            Log::warning('Inactive admin user attempted access', [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'ip' => $request->ip(),
            ]);

            Auth::logout();
            return response()->view('auth.login');
        }

        // Audit access
        Log::info('Admin dashboard access', [
            'user_id' => $user->id,
            'user_email' => $user->email,
            'ip' => $request->ip(),
            'url' => $request->fullUrl(),
            'timestamp' => now(),
        ]);

        return $next($request);
    }
}

