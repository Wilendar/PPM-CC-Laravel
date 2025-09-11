<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;
use Exception;
use Throwable;

/**
 * Google Workspace OAuth2 Controller
 * 
 * FAZA D: OAuth2 + Advanced Features
 * Handles Google Workspace authentication with enterprise features:
 * - Domain verification dla workplace accounts
 * - Automatic role assignment based on email domain
 * - Account linking dla existing users
 * - Security logging and audit trail
 * - Token management and refresh
 * - Profile photo sync from Google
 */
class GoogleAuthController extends Controller
{
    /**
     * Redirect user to Google OAuth authorization page.
     */
    public function redirect(): RedirectResponse
    {
        try {
            $this->logAuditEvent('oauth.redirect.initiated', [
                'provider' => 'google',
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent()
            ]);

            $driver = Socialite::driver('google');
            
            // Add Google Workspace specific parameters
            $scopes = config('services.google.scopes', ['openid', 'profile', 'email']);
            
            // Configure for offline access (refresh tokens)
            $parameters = [
                'access_type' => 'offline',
                'approval_prompt' => 'auto',
                'include_granted_scopes' => 'true'
            ];
            
            // Add hosted domain restriction if configured
            $hostedDomain = config('services.google.hosted_domain');
            if ($hostedDomain) {
                $parameters['hd'] = $hostedDomain;
            }
            
            return $driver
                ->scopes($scopes)
                ->with($parameters)
                ->redirect();
                
        } catch (Exception $e) {
            $this->logError('oauth.redirect.failed', $e, ['provider' => 'google']);
            
            return redirect()
                ->route('login')
                ->withErrors(['oauth' => 'Nie udało się przekierować do Google. Spróbuj ponownie.']);
        }
    }

    /**
     * Handle Google OAuth callback.
     */
    public function callback(Request $request): RedirectResponse
    {
        try {
            // Handle OAuth errors from Google
            if ($request->has('error')) {
                $this->logAuditEvent('oauth.callback.error', [
                    'provider' => 'google',
                    'error' => $request->get('error'),
                    'error_description' => $request->get('error_description'),
                    'ip' => $request->ip()
                ]);
                
                return redirect()
                    ->route('login')
                    ->withErrors(['oauth' => 'Autoryzacja Google została anulowana lub nie powiodła się.']);
            }

            // Get user data from Google
            $googleUser = Socialite::driver('google')->user();
            
            // Verify domain restrictions
            if (!$this->isAllowedDomain($googleUser->getEmail())) {
                $this->logAuditEvent('oauth.domain.rejected', [
                    'provider' => 'google',
                    'email' => $googleUser->getEmail(),
                    'domain' => $this->extractDomain($googleUser->getEmail()),
                    'ip' => $request->ip()
                ]);
                
                return redirect()
                    ->route('login')
                    ->withErrors(['oauth' => 'Twoja domena email nie jest autoryzowana do logowania.']);
            }

            // Process user authentication
            $user = $this->findOrCreateUser($googleUser, $request);
            
            // Check if account is locked
            if ($user->isOAuthLocked()) {
                $this->logAuditEvent('oauth.account.locked', [
                    'user_id' => $user->id,
                    'provider' => 'google',
                    'locked_until' => $user->oauth_locked_until,
                    'ip' => $request->ip()
                ]);
                
                return redirect()
                    ->route('login')
                    ->withErrors(['oauth' => 'Konto jest tymczasowo zablokowane. Spróbuj ponownie później.']);
            }

            // Update OAuth data and activity
            $this->updateUserOAuthData($user, $googleUser);
            
            // Log successful login
            $this->logAuditEvent('oauth.login.success', [
                'user_id' => $user->id,
                'provider' => 'google',
                'email' => $user->email,
                'oauth_email' => $user->oauth_email,
                'ip' => $request->ip()
            ]);

            // Authenticate user
            Auth::login($user, config('services.oauth.remember_oauth_sessions', true));
            
            // Update last login
            $user->updateLastLogin();
            $user->updateOAuthActivity();

            // Redirect to intended page or dashboard
            return redirect()->intended(route('dashboard'));

        } catch (InvalidStateException $e) {
            $this->logError('oauth.callback.invalid_state', $e, [
                'provider' => 'google',
                'ip' => $request->ip()
            ]);
            
            return redirect()
                ->route('login')
                ->withErrors(['oauth' => 'Sesja OAuth wygasła. Spróbuj zalogować się ponownie.']);

        } catch (Exception $e) {
            $this->logError('oauth.callback.failed', $e, [
                'provider' => 'google',
                'ip' => $request->ip()
            ]);
            
            return redirect()
                ->route('login')
                ->withErrors(['oauth' => 'Wystąpił błąd podczas logowania Google. Spróbuj ponownie.']);
        }
    }

    /**
     * Handle domain verification for Google Workspace.
     */
    public function domainVerify(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => 'required|email',
            'domain' => 'required|string'
        ]);

        try {
            $email = $request->get('email');
            $domain = $request->get('domain');
            
            // Verify if domain is in allowed list
            $allowedDomains = config('services.oauth.allowed_domains');
            if ($allowedDomains) {
                $allowedList = explode(',', $allowedDomains);
                $allowedList = array_map('trim', $allowedList);
                
                if (!in_array($domain, $allowedList)) {
                    $this->logAuditEvent('oauth.domain.verification.failed', [
                        'email' => $email,
                        'domain' => $domain,
                        'allowed_domains' => $allowedList,
                        'ip' => $request->ip()
                    ]);
                    
                    return redirect()
                        ->route('login')
                        ->withErrors(['oauth' => "Domena {$domain} nie jest autoryzowana."]);
                }
            }

            // Find user and update domain verification
            $user = User::where('oauth_email', $email)->first();
            if ($user) {
                $user->update([
                    'oauth_domain' => $domain,
                    'oauth_verified' => true
                ]);
                
                $this->logAuditEvent('oauth.domain.verification.success', [
                    'user_id' => $user->id,
                    'email' => $email,
                    'domain' => $domain,
                    'ip' => $request->ip()
                ]);
            }

            return redirect()
                ->route('dashboard')
                ->with('success', 'Domena została zweryfikowana pomyślnie.');

        } catch (Exception $e) {
            $this->logError('oauth.domain.verification.error', $e, [
                'email' => $request->get('email'),
                'domain' => $request->get('domain'),
                'ip' => $request->ip()
            ]);
            
            return redirect()
                ->route('login')
                ->withErrors(['oauth' => 'Wystąpił błąd podczas weryfikacji domeny.']);
        }
    }

    /**
     * Link Google account to existing user.
     */
    public function linkAccount(Request $request): RedirectResponse
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        try {
            $user = Auth::user();
            $googleUser = Socialite::driver('google')->user();
            
            // Check if Google account is already linked to another user
            $existingUser = User::where('oauth_provider', 'google')
                               ->where('oauth_id', $googleUser->getId())
                               ->where('id', '!=', $user->id)
                               ->first();
            
            if ($existingUser) {
                $this->logAuditEvent('oauth.link.conflict', [
                    'user_id' => $user->id,
                    'existing_user_id' => $existingUser->id,
                    'provider' => 'google',
                    'oauth_id' => $googleUser->getId(),
                    'ip' => $request->ip()
                ]);
                
                return redirect()
                    ->route('profile.show')
                    ->withErrors(['oauth' => 'To konto Google jest już powiązane z innym użytkownikiem.']);
            }

            // Link Google account
            $user->linkOAuthProvider('google', [
                'id' => $googleUser->getId(),
                'email' => $googleUser->getEmail(),
                'name' => $googleUser->getName(),
                'avatar' => $googleUser->getAvatar(),
                'token' => $googleUser->token,
                'refresh_token' => $googleUser->refreshToken,
            ]);
            
            $this->updateUserOAuthData($user, $googleUser);
            
            $this->logAuditEvent('oauth.link.success', [
                'user_id' => $user->id,
                'provider' => 'google',
                'oauth_email' => $googleUser->getEmail(),
                'ip' => $request->ip()
            ]);

            return redirect()
                ->route('profile.show')
                ->with('success', 'Konto Google zostało pomyślnie powiązane.');

        } catch (Exception $e) {
            $this->logError('oauth.link.failed', $e, [
                'user_id' => Auth::id(),
                'provider' => 'google',
                'ip' => $request->ip()
            ]);
            
            return redirect()
                ->route('profile.show')
                ->withErrors(['oauth' => 'Nie udało się powiązać konta Google.']);
        }
    }

    /**
     * Unlink Google account from user.
     */
    public function unlinkAccount(Request $request): RedirectResponse
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        try {
            $user = Auth::user();
            
            if (!$user->isOAuthUser() || $user->oauth_provider !== 'google') {
                return redirect()
                    ->route('profile.show')
                    ->withErrors(['oauth' => 'Konto Google nie jest powiązane.']);
            }

            $this->logAuditEvent('oauth.unlink.initiated', [
                'user_id' => $user->id,
                'provider' => 'google',
                'oauth_email' => $user->oauth_email,
                'ip' => $request->ip()
            ]);

            $user->unlinkOAuthProvider('google');
            
            $this->logAuditEvent('oauth.unlink.success', [
                'user_id' => $user->id,
                'provider' => 'google',
                'ip' => $request->ip()
            ]);

            return redirect()
                ->route('profile.show')
                ->with('success', 'Konto Google zostało odłączone.');

        } catch (Exception $e) {
            $this->logError('oauth.unlink.failed', $e, [
                'user_id' => Auth::id(),
                'provider' => 'google',
                'ip' => $request->ip()
            ]);
            
            return redirect()
                ->route('profile.show')
                ->withErrors(['oauth' => 'Nie udało się odłączyć konta Google.']);
        }
    }

    /**
     * Find existing user or create new one from Google data.
     */
    protected function findOrCreateUser($googleUser, Request $request): User
    {
        // First, try to find by OAuth provider and ID
        $user = User::where('oauth_provider', 'google')
                   ->where('oauth_id', $googleUser->getId())
                   ->first();
        
        if ($user) {
            return $user;
        }

        // Try to find by email (existing account linking)
        $user = User::where('email', $googleUser->getEmail())->first();
        
        if ($user && config('services.oauth.link_existing_accounts', true)) {
            // Link existing account to Google
            $user->update([
                'oauth_provider' => 'google',
                'oauth_id' => $googleUser->getId(),
                'oauth_email' => $googleUser->getEmail(),
                'oauth_verified' => true,
                'oauth_linked_at' => now(),
                'primary_auth_method' => 'google'
            ]);
            
            $this->logAuditEvent('oauth.account.linked', [
                'user_id' => $user->id,
                'provider' => 'google',
                'email' => $user->email,
                'oauth_email' => $googleUser->getEmail(),
                'ip' => $request->ip()
            ]);
            
            return $user;
        }

        // Create new user if auto-registration is enabled
        if (config('services.oauth.auto_registration', true)) {
            $user = User::create([
                'name' => $googleUser->getName(),
                'email' => $googleUser->getEmail(),
                'password' => Hash::make(Str::random(32)), // Random password
                'email_verified_at' => now(), // OAuth users are pre-verified
                'oauth_provider' => 'google',
                'oauth_id' => $googleUser->getId(),
                'oauth_email' => $googleUser->getEmail(),
                'oauth_verified' => true,
                'oauth_linked_at' => now(),
                'oauth_domain' => $this->extractDomain($googleUser->getEmail()),
                'primary_auth_method' => 'google',
                'is_active' => true,
                'ui_preferences' => User::getDefaultUIPreferences(),
                'notification_settings' => User::getDefaultNotificationSettings(),
            ]);

            // Assign default role based on domain or configuration
            $this->assignUserRole($user);
            
            $this->logAuditEvent('oauth.user.created', [
                'user_id' => $user->id,
                'provider' => 'google',
                'email' => $user->email,
                'domain' => $user->oauth_domain,
                'ip' => $request->ip()
            ]);
            
            return $user;
        }

        throw new Exception('Auto-registration is disabled and no existing account found.');
    }

    /**
     * Update user's OAuth data from Google.
     */
    protected function updateUserOAuthData(User $user, $googleUser): void
    {
        $updates = [
            'oauth_provider' => 'google',
            'oauth_id' => $googleUser->getId(),
            'oauth_email' => $googleUser->getEmail(),
            'oauth_access_token' => encrypt($googleUser->token),
            'oauth_token_expires_at' => $googleUser->expiresIn ? now()->addSeconds($googleUser->expiresIn) : null,
            'oauth_verified' => true,
            'oauth_domain' => $this->extractDomain($googleUser->getEmail()),
            'oauth_last_used_at' => now(),
            'oauth_login_attempts' => 0
        ];
        
        // Add refresh token if available (only on first auth or re-auth)
        if ($googleUser->refreshToken) {
            $updates['oauth_refresh_token'] = encrypt($googleUser->refreshToken);
        }
        
        // Sync avatar if enabled
        if (config('services.oauth.sync_avatars', true) && $googleUser->getAvatar()) {
            $updates['oauth_avatar_url'] = $googleUser->getAvatar();
        }
        
        // Sync profile data if enabled
        if (config('services.oauth.sync_profile_data', true)) {
            $updates['oauth_provider_data'] = [
                'google' => [
                    'id' => $googleUser->getId(),
                    'name' => $googleUser->getName(),
                    'email' => $googleUser->getEmail(),
                    'avatar' => $googleUser->getAvatar(),
                    'updated_at' => now()->toISOString()
                ]
            ];
        }
        
        $user->update($updates);
    }

    /**
     * Assign role to new OAuth user based on domain or configuration.
     */
    protected function assignUserRole(User $user): void
    {
        // Default role for OAuth users
        $defaultRole = 'User';
        
        // Check if domain-based role assignment is configured
        $domain = $user->oauth_domain;
        $domainRoles = config('auth.oauth_domain_roles', []);
        
        if (isset($domainRoles[$domain])) {
            $defaultRole = $domainRoles[$domain];
        }
        
        $user->assignRole($defaultRole);
    }

    /**
     * Check if email domain is allowed for OAuth.
     */
    protected function isAllowedDomain(string $email): bool
    {
        $allowedDomains = config('services.oauth.allowed_domains');
        
        if (!$allowedDomains) {
            return true; // No domain restrictions
        }
        
        $domainList = explode(',', $allowedDomains);
        $domainList = array_map('trim', $domainList);
        
        $userDomain = $this->extractDomain($email);
        
        return in_array($userDomain, $domainList);
    }

    /**
     * Extract domain from email address.
     */
    protected function extractDomain(string $email): string
    {
        return substr(strrchr($email, "@"), 1);
    }

    /**
     * Log audit event for OAuth operations.
     */
    protected function logAuditEvent(string $action, array $data = []): void
    {
        try {
            AuditLog::create([
                'user_id' => Auth::id(),
                'action' => $action,
                'description' => "OAuth Google: {$action}",
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'data' => $data
            ]);
        } catch (Throwable $e) {
            Log::error('Failed to create audit log', [
                'action' => $action,
                'data' => $data,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Log error with context.
     */
    protected function logError(string $context, Throwable $e, array $additionalData = []): void
    {
        Log::error("OAuth Google Error: {$context}", [
            'exception' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString(),
            'additional_data' => $additionalData
        ]);
        
        $this->logAuditEvent($context . '.error', array_merge($additionalData, [
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]));
    }
}