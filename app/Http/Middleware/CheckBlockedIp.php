<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\BlockedIp;
use Symfony\Component\HttpFoundation\Response;

/**
 * ETAP_04: Check Blocked IP Middleware
 *
 * Blocks requests from IP addresses that have been banned.
 * Registered as alias 'check.blocked.ip' - NOT applied globally.
 */
class CheckBlockedIp
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (BlockedIp::isBlocked($request->ip())) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Twoj adres IP zostal zablokowany.',
                ], 403);
            }

            abort(403, 'Twoj adres IP zostal zablokowany.');
        }

        return $next($request);
    }
}
