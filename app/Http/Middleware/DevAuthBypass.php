<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\SystemSetting;
use App\Models\User;
use Symfony\Component\HttpFoundation\Response;

/**
 * Development Authentication Bypass Middleware
 *
 * W trybie dev_auth_bypass automatycznie loguje użytkownika z ID=8 (Admin).
 * NIGDY nie używać na produkcji!
 */
class DevAuthBypass
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Sprawdź czy dev_auth_bypass jest włączony
        $devAuthBypass = false;

        try {
            $devAuthBypass = SystemSetting::get('dev_auth_bypass', env('DEV_AUTH_BYPASS', false));
        } catch (\Exception $e) {
            $devAuthBypass = env('DEV_AUTH_BYPASS', false);
        }

        // Jeśli dev mode i brak zalogowanego użytkownika - auto-login admin
        if ($devAuthBypass && !Auth::check()) {
            // Zaloguj admina (ID=8)
            $adminUser = User::find(8);

            if ($adminUser) {
                Auth::login($adminUser);
            }
        }

        return $next($request);
    }
}
