<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;
use Exception;
use Throwable;

/**
 * Microsoft Entra ID (Azure AD) OAuth2 Controller
 * 
 * FAZA D: OAuth2 + Advanced Features
 * Handles Microsoft Entra ID authentication with enterprise features:
 * - Single Sign-On (SSO) dla Microsoft 365 users
 * - Microsoft Graph API integration
 * - Profile sync (name, email, job title, department)
 * - Photo sync from Microsoft Graph
 * - Group membership mapping to PPM roles
 * - Account linking dla existing users
 * - Security logging and audit trail
 */
class MicrosoftAuthController extends Controller
{
    /**
     * Redirect user to Microsoft OAuth authorization page.
     */
    public function redirect(): RedirectResponse
    {
        try {
            $this->logAuditEvent('oauth.redirect.initiated', [
                'provider' => 'microsoft',
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent()
            ]);

            $driver = Socialite::driver('microsoft-azure');
            
            // Microsoft Entra ID specific parameters
            $scopes = config('services.microsoft.scopes', ['openid', 'profile', 'email', 'User.Read']);
            
            $parameters = [
                'response_mode' => 'query',
                'prompt' => 'select_account' // Always show account picker
            ];
            
            // Add tenant restriction if configured
            $tenant = config('services.microsoft.tenant', 'common');
            if ($tenant !== 'common') {
                $parameters['tenant'] = $tenant;
            }
            
            return $driver
                ->scopes($scopes)
                ->with($parameters)
                ->redirect();
                
        } catch (Exception $e) {
            $this->logError('oauth.redirect.failed', $e, ['provider' => 'microsoft']);
            
            return redirect()
                ->route('login')
                ->withErrors(['oauth' => 'Nie udało się przekierować do Microsoft. Spróbuj ponownie.']);
        }
    }

    /**
     * Handle Microsoft OAuth callback.
     */
    public function callback(Request $request): RedirectResponse
    {
        try {
            // Handle OAuth errors from Microsoft
            if ($request->has('error')) {
                $this->logAuditEvent('oauth.callback.error', [
                    'provider' => 'microsoft',
                    'error' => $request->get('error'),
                    'error_description' => $request->get('error_description'),
                    'ip' => $request->ip()
                ]);
                
                return redirect()
                    ->route('login')
                    ->withErrors(['oauth' => 'Autoryzacja Microsoft została anulowana lub nie powiodła się.']);
            }

            // Get user data from Microsoft
            $microsoftUser = Socialite::driver('microsoft-azure')->user();
            
            // Verify domain restrictions
            if (!$this->isAllowedDomain($microsoftUser->getEmail())) {
                $this->logAuditEvent('oauth.domain.rejected', [
                    'provider' => 'microsoft',
                    'email' => $microsoftUser->getEmail(),
                    'domain' => $this->extractDomain($microsoftUser->getEmail()),
                    'ip' => $request->ip()
                ]);
                
                return redirect()
                    ->route('login')
                    ->withErrors(['oauth' => 'Twoja domena email nie jest autoryzowana do logowania.']);
            }

            // Process user authentication
            $user = $this->findOrCreateUser($microsoftUser, $request);
            
            // Check if account is locked
            if ($user->isOAuthLocked()) {
                $this->logAuditEvent('oauth.account.locked', [
                    'user_id' => $user->id,
                    'provider' => 'microsoft',
                    'locked_until' => $user->oauth_locked_until,
                    'ip' => $request->ip()
                ]);
                
                return redirect()
                    ->route('login')
                    ->withErrors(['oauth' => 'Konto jest tymczasowo zablokowane. Spróbuj ponownie później.']);
            }

            // Update OAuth data and activity
            $this->updateUserOAuthData($user, $microsoftUser);
            
            // Sync additional Microsoft Graph data
            $this->syncMicrosoftGraphData($user, $microsoftUser->token);
            
            // Log successful login
            $this->logAuditEvent('oauth.login.success', [
                'user_id' => $user->id,
                'provider' => 'microsoft',
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
                'provider' => 'microsoft',
                'ip' => $request->ip()
            ]);
            
            return redirect()
                ->route('login')
                ->withErrors(['oauth' => 'Sesja OAuth wygasła. Spróbuj zalogować się ponownie.']);

        } catch (Exception $e) {
            $this->logError('oauth.callback.failed', $e, [
                'provider' => 'microsoft',
                'ip' => $request->ip()
            ]);
            
            return redirect()
                ->route('login')
                ->withErrors(['oauth' => 'Wystąpił błąd podczas logowania Microsoft. Spróbuj ponownie.']);
        }
    }

    /**
     * Link Microsoft account to existing user.
     */
    public function linkAccount(Request $request): RedirectResponse
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        try {
            $user = Auth::user();
            $microsoftUser = Socialite::driver('microsoft-azure')->user();
            
            // Check if Microsoft account is already linked to another user
            $existingUser = User::where('oauth_provider', 'microsoft')
                               ->where('oauth_id', $microsoftUser->getId())
                               ->where('id', '!=', $user->id)
                               ->first();
            
            if ($existingUser) {
                $this->logAuditEvent('oauth.link.conflict', [
                    'user_id' => $user->id,
                    'existing_user_id' => $existingUser->id,
                    'provider' => 'microsoft',
                    'oauth_id' => $microsoftUser->getId(),
                    'ip' => $request->ip()
                ]);
                
                return redirect()
                    ->route('profile.show')
                    ->withErrors(['oauth' => 'To konto Microsoft jest już powiązane z innym użytkownikiem.']);
            }

            // Link Microsoft account
            $user->linkOAuthProvider('microsoft', [
                'id' => $microsoftUser->getId(),
                'email' => $microsoftUser->getEmail(),
                'name' => $microsoftUser->getName(),
                'avatar' => $microsoftUser->getAvatar(),
                'token' => $microsoftUser->token,
                'refresh_token' => $microsoftUser->refreshToken,
            ]);
            
            $this->updateUserOAuthData($user, $microsoftUser);
            $this->syncMicrosoftGraphData($user, $microsoftUser->token);
            
            $this->logAuditEvent('oauth.link.success', [
                'user_id' => $user->id,
                'provider' => 'microsoft',
                'oauth_email' => $microsoftUser->getEmail(),
                'ip' => $request->ip()
            ]);

            return redirect()
                ->route('profile.show')
                ->with('success', 'Konto Microsoft zostało pomyślnie powiązane.');

        } catch (Exception $e) {
            $this->logError('oauth.link.failed', $e, [
                'user_id' => Auth::id(),
                'provider' => 'microsoft',
                'ip' => $request->ip()
            ]);
            
            return redirect()
                ->route('profile.show')
                ->withErrors(['oauth' => 'Nie udało się powiązać konta Microsoft.']);
        }
    }

    /**
     * Unlink Microsoft account from user.
     */
    public function unlinkAccount(Request $request): RedirectResponse
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        try {
            $user = Auth::user();
            
            if (!$user->isOAuthUser() || $user->oauth_provider !== 'microsoft') {
                return redirect()
                    ->route('profile.show')
                    ->withErrors(['oauth' => 'Konto Microsoft nie jest powiązane.']);
            }

            $this->logAuditEvent('oauth.unlink.initiated', [
                'user_id' => $user->id,
                'provider' => 'microsoft',
                'oauth_email' => $user->oauth_email,
                'ip' => $request->ip()
            ]);

            $user->unlinkOAuthProvider('microsoft');
            
            $this->logAuditEvent('oauth.unlink.success', [
                'user_id' => $user->id,
                'provider' => 'microsoft',
                'ip' => $request->ip()
            ]);

            return redirect()
                ->route('profile.show')
                ->with('success', 'Konto Microsoft zostało odłączone.');

        } catch (Exception $e) {
            $this->logError('oauth.unlink.failed', $e, [
                'user_id' => Auth::id(),
                'provider' => 'microsoft',
                'ip' => $request->ip()
            ]);
            
            return redirect()
                ->route('profile.show')
                ->withErrors(['oauth' => 'Nie udało się odłączyć konta Microsoft.']);
        }
    }

    /**
     * Find existing user or create new one from Microsoft data.
     */
    protected function findOrCreateUser($microsoftUser, Request $request): User
    {
        // First, try to find by OAuth provider and ID
        $user = User::where('oauth_provider', 'microsoft')
                   ->where('oauth_id', $microsoftUser->getId())
                   ->first();
        
        if ($user) {
            return $user;
        }

        // Try to find by email (existing account linking)
        $user = User::where('email', $microsoftUser->getEmail())->first();
        
        if ($user && config('services.oauth.link_existing_accounts', true)) {
            // Link existing account to Microsoft
            $user->update([
                'oauth_provider' => 'microsoft',
                'oauth_id' => $microsoftUser->getId(),
                'oauth_email' => $microsoftUser->getEmail(),
                'oauth_verified' => true,
                'oauth_linked_at' => now(),
                'primary_auth_method' => 'microsoft'
            ]);
            
            $this->logAuditEvent('oauth.account.linked', [
                'user_id' => $user->id,
                'provider' => 'microsoft',
                'email' => $user->email,
                'oauth_email' => $microsoftUser->getEmail(),
                'ip' => $request->ip()
            ]);
            
            return $user;
        }

        // Create new user if auto-registration is enabled
        if (config('services.oauth.auto_registration', true)) {
            $user = User::create([
                'name' => $microsoftUser->getName(),
                'email' => $microsoftUser->getEmail(),
                'password' => Hash::make(Str::random(32)), // Random password
                'email_verified_at' => now(), // OAuth users are pre-verified
                'oauth_provider' => 'microsoft',
                'oauth_id' => $microsoftUser->getId(),
                'oauth_email' => $microsoftUser->getEmail(),
                'oauth_verified' => true,
                'oauth_linked_at' => now(),
                'oauth_domain' => $this->extractDomain($microsoftUser->getEmail()),
                'primary_auth_method' => 'microsoft',
                'is_active' => true,
                'ui_preferences' => User::getDefaultUIPreferences(),
                'notification_settings' => User::getDefaultNotificationSettings(),
            ]);

            // Assign default role based on domain or configuration
            $this->assignUserRole($user);
            
            $this->logAuditEvent('oauth.user.created', [
                'user_id' => $user->id,
                'provider' => 'microsoft',
                'email' => $user->email,
                'domain' => $user->oauth_domain,
                'ip' => $request->ip()
            ]);
            
            return $user;
        }

        throw new Exception('Auto-registration is disabled and no existing account found.');
    }

    /**
     * Update user's OAuth data from Microsoft.
     */
    protected function updateUserOAuthData(User $user, $microsoftUser): void
    {
        $updates = [
            'oauth_provider' => 'microsoft',
            'oauth_id' => $microsoftUser->getId(),
            'oauth_email' => $microsoftUser->getEmail(),
            'oauth_access_token' => encrypt($microsoftUser->token),
            'oauth_token_expires_at' => $microsoftUser->expiresIn ? now()->addSeconds($microsoftUser->expiresIn) : null,
            'oauth_verified' => true,
            'oauth_domain' => $this->extractDomain($microsoftUser->getEmail()),
            'oauth_last_used_at' => now(),
            'oauth_login_attempts' => 0
        ];
        
        // Add refresh token if available
        if ($microsoftUser->refreshToken) {
            $updates['oauth_refresh_token'] = encrypt($microsoftUser->refreshToken);
        }
        
        // Sync avatar if enabled
        if (config('services.oauth.sync_avatars', true) && $microsoftUser->getAvatar()) {
            $updates['oauth_avatar_url'] = $microsoftUser->getAvatar();
        }
        
        $user->update($updates);
    }

    /**
     * Sync additional data from Microsoft Graph API.
     */
    protected function syncMicrosoftGraphData(User $user, string $accessToken): void
    {
        try {
            // Get user profile from Microsoft Graph
            $response = Http::withToken($accessToken)
                           ->timeout(10)
                           ->get('https://graph.microsoft.com/v1.0/me', [
                               '$select' => 'id,displayName,mail,userPrincipalName,jobTitle,department,officeLocation,businessPhones,mobilePhone,companyName'
                           ]);
            
            if ($response->successful()) {
                $profile = $response->json();
                
                // Get photo from Microsoft Graph
                $photoResponse = Http::withToken($accessToken)
                                   ->timeout(5)
                                   ->get('https://graph.microsoft.com/v1.0/me/photo/$value');
                
                $photoUrl = null;
                if ($photoResponse->successful() && $photoResponse->header('Content-Type')) {
                    // For now, we'll store the URL to fetch the photo
                    $photoUrl = 'https://graph.microsoft.com/v1.0/me/photo/$value';
                }
                
                // Update user with Microsoft Graph data
                $updates = [
                    'oauth_provider_data' => array_merge(
                        $user->oauth_provider_data ?? [],
                        [
                            'microsoft' => [
                                'graph_profile' => $profile,
                                'photo_url' => $photoUrl,
                                'updated_at' => now()->toISOString()
                            ]
                        ]
                    )
                ];
                
                // Update profile fields if sync is enabled
                if (config('services.oauth.sync_profile_data', true)) {
                    if (!empty($profile['jobTitle'])) {
                        $updates['position'] = $profile['jobTitle'];
                    }
                    
                    if (!empty($profile['companyName'])) {
                        $updates['company'] = $profile['companyName'];
                    }
                    
                    if (!empty($profile['mobilePhone'])) {
                        $updates['phone'] = $profile['mobilePhone'];
                    } elseif (!empty($profile['businessPhones'][0])) {
                        $updates['phone'] = $profile['businessPhones'][0];
                    }
                }
                
                // Update avatar URL if photo is available
                if ($photoUrl && config('services.oauth.sync_avatars', true)) {
                    $updates['oauth_avatar_url'] = $photoUrl;
                }
                
                $user->update($updates);
                
                $this->logAuditEvent('oauth.graph.sync.success', [
                    'user_id' => $user->id,
                    'provider' => 'microsoft',
                    'synced_fields' => array_keys($updates)
                ]);
                
            } else {
                $this->logAuditEvent('oauth.graph.sync.failed', [
                    'user_id' => $user->id,
                    'provider' => 'microsoft',
                    'error' => 'Graph API response failed',
                    'status' => $response->status()
                ]);
            }
            
        } catch (Exception $e) {
            $this->logError('oauth.graph.sync.error', $e, [
                'user_id' => $user->id,
                'provider' => 'microsoft'
            ]);
        }
    }

    /**
     * Assign role to new OAuth user based on domain or Microsoft Graph groups.
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
        
        // TODO: Future enhancement - check Microsoft Graph groups for role mapping
        // This would require additional permissions and configuration
        
        $user->assignRole($defaultRole);
    }

    /**
     * Check if email domain is allowed for OAuth.
     */
    protected function isAllowedDomain(string $email): bool
    {
        $allowedDomains = config('services.microsoft.allowed_domains') ?? config('services.oauth.allowed_domains');
        
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
                'description' => "OAuth Microsoft: {$action}",
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
        Log::error("OAuth Microsoft Error: {$context}", [
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