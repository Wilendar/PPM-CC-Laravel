<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * PPM Permission Middleware
 * 
 * Middleware do sprawdzania permissions użytkownika z obsługą Spatie Permission.
 * Usage: Route::middleware(['auth', 'permission:products.create'])
 * 
 * FAZA A: Spatie Setup + Middleware
 * 
 * Obsługuje:
 * - Single permission: permission:products.create
 * - Multiple permissions: permission:products.create,products.edit
 * - Admin bypass (Admin zawsze ma wszystkie permissions)
 * - Proper error handling z audit logging
 */
class PermissionMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, ...$permissions): Response
    {
        // Sprawdź czy użytkownik jest zalogowany
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();

        // Admin zawsze ma wszystkie permissions
        if ($user->hasRole('Admin')) {
            return $next($request);
        }

        // Sprawdź czy użytkownik ma wszystkie wymagane permissions
        foreach ($permissions as $permission) {
            if (!$user->hasPermissionTo($permission)) {
                // Log access attempt dla audit
                logger('Permission access denied', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'required_permission' => $permission,
                    'user_permissions' => $user->getAllPermissions()->pluck('name')->toArray(),
                    'user_roles' => $user->getRoleNames(),
                    'url' => $request->fullUrl(),
                    'ip' => $request->ip(),
                ]);

                // Zwróć 403 dla AJAX/API requests
                if ($request->expectsJson()) {
                    return response()->json([
                        'error' => 'Insufficient permissions',
                        'message' => "You do not have the required permission: {$permission}",
                        'required_permissions' => $permissions
                    ], 403);
                }

                // Redirect dla web requests z error message
                return redirect()->route('dashboard')->withErrors([
                    'access' => "You do not have the required permission: {$permission}"
                ]);
            }
        }

        return $next($request);
    }
}