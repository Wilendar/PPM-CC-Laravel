<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\User\SessionManagementService;
use Symfony\Component\HttpFoundation\Response;

/**
 * ETAP_04 FAZA A: Track User Session Middleware
 *
 * Updates session activity on each request.
 */
class TrackUserSessionMiddleware
{
    public function __construct(
        protected SessionManagementService $sessionService
    ) {}

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Only track for authenticated users
        if (Auth::check()) {
            $sessionId = session()->getId();

            // Update last activity
            $this->sessionService->updateSessionActivity(
                $sessionId,
                $request->path()
            );
        }

        return $next($request);
    }
}
