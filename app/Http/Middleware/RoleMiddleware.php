<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * PPM Role Middleware
 * 
 * Middleware do sprawdzania ról użytkownika z obsługą hierarchii 7-poziomowej.
 * Usage: Route::middleware(['auth', 'role:Admin,Manager'])
 * 
 * FAZA A: Spatie Setup + Middleware
 * 
 * Obsługuje:
 * - Single role: role:Admin
 * - Multiple roles: role:Admin,Manager,Editor
 * - Hierarchical access (Admin zawsze ma dostęp)
 * - Proper error handling z custom 403 page
 */
class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        // Sprawdź czy użytkownik jest zalogowany
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();

        // Admin zawsze ma dostęp do wszystkiego
        if ($user->hasRole('Admin')) {
            return $next($request);
        }

        // Sprawdź czy użytkownik ma wymaganą rolę
        if ($user->hasAnyRole($roles)) {
            return $next($request);
        }

        // Log access attempt dla audit
        logger('Role access denied', [
            'user_id' => $user->id,
            'email' => $user->email,
            'required_roles' => $roles,
            'user_roles' => $user->getRoleNames(),
            'url' => $request->fullUrl(),
            'ip' => $request->ip(),
        ]);

        // Zwróć 403 dla AJAX/API requests
        if ($request->expectsJson()) {
            return response()->json([
                'error' => 'Access denied',
                'message' => 'You do not have permission to access this resource.',
                'required_roles' => $roles
            ], 403);
        }

        // Redirect dla web requests z error message
        return redirect()->route('dashboard')->withErrors([
            'access' => 'You do not have permission to access this resource.'
        ]);
    }
}