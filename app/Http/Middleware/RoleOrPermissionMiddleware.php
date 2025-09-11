<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * PPM Role Or Permission Middleware
 * 
 * Middleware do sprawdzania ról LUB permissions użytkownika (OR logic).
 * Usage: Route::middleware(['auth', 'role_or_permission:Admin|products.create'])
 * 
 * FAZA A: Spatie Setup + Middleware
 * 
 * Obsługuje:
 * - Role OR permission: role_or_permission:Admin|products.create
 * - Multiple options: role_or_permission:Admin,Manager|products.create,products.edit
 * - Flexible access control dla complex scenarios
 * - Admin bypass zawsze aktywny
 */
class RoleOrPermissionMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $rolesOrPermissions): Response
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

        // Parse roles i permissions z stringa (format: "Admin,Manager|products.create,products.edit")
        $parts = explode('|', $rolesOrPermissions);
        $roles = !empty($parts[0]) ? explode(',', $parts[0]) : [];
        $permissions = isset($parts[1]) && !empty($parts[1]) ? explode(',', $parts[1]) : [];

        $hasAccess = false;
        $accessReason = '';

        // Sprawdź role (OR logic)
        if (!empty($roles)) {
            foreach ($roles as $role) {
                $role = trim($role);
                if ($user->hasRole($role)) {
                    $hasAccess = true;
                    $accessReason = "role:{$role}";
                    break;
                }
            }
        }

        // Jeśli nie ma dostępu przez role, sprawdź permissions (OR logic)
        if (!$hasAccess && !empty($permissions)) {
            foreach ($permissions as $permission) {
                $permission = trim($permission);
                if ($user->hasPermissionTo($permission)) {
                    $hasAccess = true;
                    $accessReason = "permission:{$permission}";
                    break;
                }
            }
        }

        // Jeśli ma dostęp, kontynuuj
        if ($hasAccess) {
            // Log successful access dla audit (optional)
            logger('Role or permission access granted', [
                'user_id' => $user->id,
                'email' => $user->email,
                'access_reason' => $accessReason,
                'url' => $request->fullUrl()
            ]);
            
            return $next($request);
        }

        // Brak dostępu - log i zwróć error
        logger('Role or permission access denied', [
            'user_id' => $user->id,
            'email' => $user->email,
            'required_roles' => $roles,
            'required_permissions' => $permissions,
            'user_roles' => $user->getRoleNames(),
            'user_permissions' => $user->getAllPermissions()->pluck('name')->toArray(),
            'url' => $request->fullUrl(),
            'ip' => $request->ip(),
        ]);

        // Zwróć 403 dla AJAX/API requests
        if ($request->expectsJson()) {
            return response()->json([
                'error' => 'Access denied',
                'message' => 'You do not have the required role or permission to access this resource.',
                'required_roles' => $roles,
                'required_permissions' => $permissions
            ], 403);
        }

        // Redirect dla web requests z error message
        return redirect()->route('dashboard')->withErrors([
            'access' => 'You do not have the required role or permission to access this resource.'
        ]);
    }
}