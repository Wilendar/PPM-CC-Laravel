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
     * Zapewnia że tylko użytkownicy z rolą Admin mogą dostać się do admin dashboard.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Sprawdź czy użytkownik jest zalogowany
        if (!Auth::check()) {
            Log::warning('Unauthorized admin access attempt', [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'url' => $request->fullUrl()
            ]);
            
            return redirect()->route('login')->with('error', 'Musisz być zalogowany aby uzyskać dostęp do panelu administratora.');
        }

        $user = Auth::user();

        // Sprawdź czy użytkownik ma rolę Admin
        if (!$user->hasRole('Admin')) {
            Log::warning('Non-admin user attempted to access admin area', [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'user_roles' => $user->getRoleNames(),
                'ip' => $request->ip(),
                'url' => $request->fullUrl()
            ]);
            
            abort(403, 'Dostęp zabroniony. Wymagane uprawnienia administratora.');
        }

        // Sprawdź czy konto jest aktywne
        if (!$user->is_active) {
            Log::warning('Inactive admin user attempted access', [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'ip' => $request->ip()
            ]);
            
            Auth::logout();
            return redirect()->route('login')->with('error', 'Twoje konto zostało dezaktywowane.');
        }

        // Log successful admin access dla audit
        Log::info('Admin dashboard access', [
            'user_id' => $user->id,
            'user_email' => $user->email,
            'ip' => $request->ip(),
            'url' => $request->fullUrl(),
            'timestamp' => now()
        ]);

        return $next($request);
    }
}