<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\User\PasswordPolicyService;
use Symfony\Component\HttpFoundation\Response;

/**
 * ETAP_04 FAZA A: Force Password Change Middleware
 *
 * Redirects users who need to change their password.
 */
class ForcePasswordChangeMiddleware
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

        // Check if force password change flag is set
        if ($user->force_password_change) {
            return $this->redirectToPasswordChange($request, 'Musisz zmienic haslo przed kontynuowaniem.');
        }

        // Check if password is expired
        if ($this->passwordService->isPasswordExpired($user)) {
            return $this->redirectToPasswordChange($request, 'Twoje haslo wygaslo. Musisz je zmienic.');
        }

        return $next($request);
    }

    /**
     * Check if request should be excluded from password change check.
     */
    protected function isExcludedPath(Request $request): bool
    {
        $excludedPaths = [
            'password/change',
            'password/change/*',
            'logout',
            'api/*',
        ];

        foreach ($excludedPaths as $path) {
            if ($request->is($path)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Redirect to password change page.
     */
    protected function redirectToPasswordChange(Request $request, string $message): Response
    {
        // Don't redirect if already on password change or logout path
        if ($this->isExcludedPath($request)) {
            return app()->call([$this, 'handle'], [
                'request' => $request,
                'next' => fn($req) => response()->json(['redirect' => route('password.change')]),
            ]);
        }

        // Store intended URL
        if (!$request->is('password/change*')) {
            session()->put('url.intended', $request->url());
        }

        // Check if AJAX request
        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'redirect' => route('password.change'),
                'force_password_change' => true,
            ], 403);
        }

        return redirect()->route('password.change')
            ->with('warning', $message);
    }
}
