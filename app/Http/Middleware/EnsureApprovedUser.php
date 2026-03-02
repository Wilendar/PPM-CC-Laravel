<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * EnsureApprovedUser Middleware
 *
 * Blocks unapproved OAuth users from accessing the panel.
 * New users created via OAuth have is_approved = false by default.
 * Admin must approve them before they gain access.
 */
class EnsureApprovedUser
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && !$user->is_approved) {
            // Allow logout so unapproved users are not trapped
            if ($request->routeIs('logout')) {
                return $next($request);
            }

            // Allow the pending page itself to avoid redirect loop
            if ($request->routeIs('approval.pending')) {
                return $next($request);
            }

            return redirect()->route('approval.pending')
                ->with('warning', 'Twoje konto oczekuje na zatwierdzenie przez administratora.');
        }

        return $next($request);
    }
}
