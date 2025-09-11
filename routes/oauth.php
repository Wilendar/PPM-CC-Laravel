<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\Auth\MicrosoftAuthController;

/*
|--------------------------------------------------------------------------
| OAuth2 Routes
|--------------------------------------------------------------------------
|
| FAZA D: OAuth2 + Advanced Features
| Routes dla OAuth2 integration z Google Workspace i Microsoft Entra ID
|
| Features:
| - Google Workspace OAuth flow
| - Microsoft Entra ID OAuth flow  
| - Account linking/unlinking
| - Domain verification
| - Security monitoring endpoints
|
*/

// ==========================================
// GOOGLE WORKSPACE OAUTH ROUTES
// ==========================================

Route::prefix('auth/google')->name('auth.google.')->group(function () {
    
    // OAuth flow routes
    Route::get('/', [GoogleAuthController::class, 'redirect'])
         ->name('redirect')
         ->middleware(['throttle:oauth-redirect']);
    
    Route::get('/callback', [GoogleAuthController::class, 'callback'])
         ->name('callback')
         ->middleware(['throttle:oauth-callback']);
    
    // Domain verification for Google Workspace
    Route::post('/domain-verify', [GoogleAuthController::class, 'domainVerify'])
         ->name('domain.verify')
         ->middleware(['throttle:oauth-verify']);
    
    // Account management routes (authenticated users only)
    Route::middleware(['auth'])->group(function () {
        
        Route::post('/link', [GoogleAuthController::class, 'linkAccount'])
             ->name('link')
             ->middleware(['throttle:oauth-link']);
        
        Route::delete('/unlink', [GoogleAuthController::class, 'unlinkAccount'])
             ->name('unlink')
             ->middleware(['throttle:oauth-unlink']);
    });
});

// ==========================================
// MICROSOFT ENTRA ID OAUTH ROUTES
// ==========================================

Route::prefix('auth/microsoft')->name('auth.microsoft.')->group(function () {
    
    // OAuth flow routes
    Route::get('/', [MicrosoftAuthController::class, 'redirect'])
         ->name('redirect')
         ->middleware(['throttle:oauth-redirect']);
    
    Route::get('/callback', [MicrosoftAuthController::class, 'callback'])
         ->name('callback')
         ->middleware(['throttle:oauth-callback']);
    
    // Account management routes (authenticated users only)
    Route::middleware(['auth'])->group(function () {
        
        Route::post('/link', [MicrosoftAuthController::class, 'linkAccount'])
             ->name('link')
             ->middleware(['throttle:oauth-link']);
        
        Route::delete('/unlink', [MicrosoftAuthController::class, 'unlinkAccount'])
             ->name('unlink')
             ->middleware(['throttle:oauth-unlink']);
    });
});

// ==========================================
// OAUTH SECURITY & MONITORING ROUTES
// ==========================================

Route::prefix('auth/oauth')->name('auth.oauth.')->middleware(['auth'])->group(function () {
    
    // OAuth security dashboard (Admin only)
    Route::middleware(['admin'])->group(function () {
        
        Route::get('/security', function () {
            return view('admin.oauth.security.index');
        })->name('security.index');
        
        Route::get('/audit-logs', function () {
            return view('admin.oauth.audit-logs.index');
        })->name('audit.index');
        
        Route::get('/suspicious-activity', function () {
            return view('admin.oauth.security.suspicious');
        })->name('security.suspicious');
        
        Route::get('/compliance-report', function () {
            return view('admin.oauth.compliance.index');
        })->name('compliance.index');
    });
    
    // User OAuth management
    Route::get('/providers', function () {
        return view('profile.oauth.providers');
    })->name('providers.index');
    
    Route::get('/status', function () {
        $user = auth()->user();
        return response()->json([
            'is_oauth_user' => $user->isOAuthUser(),
            'provider' => $user->oauth_provider,
            'verified' => $user->oauth_verified,
            'linked_providers' => $user->oauth_linked_providers ?? [],
            'primary_auth_method' => $user->primary_auth_method,
            'last_oauth_login' => $user->oauth_last_used_at?->toISOString(),
        ]);
    })->name('status');
});

// ==========================================
// OAUTH API ROUTES (for AJAX/SPA)
// ==========================================

Route::prefix('api/oauth')->name('api.oauth.')->middleware(['auth', 'api'])->group(function () {
    
    // Provider status check
    Route::get('/providers/status', function () {
        $enabledProviders = explode(',', config('services.oauth.enabled_providers', ''));
        $enabledProviders = array_map('trim', $enabledProviders);
        
        return response()->json([
            'enabled_providers' => $enabledProviders,
            'google_enabled' => in_array('google', $enabledProviders),
            'microsoft_enabled' => in_array('microsoft', $enabledProviders),
            'auto_registration' => config('services.oauth.auto_registration', true),
            'link_existing' => config('services.oauth.link_existing_accounts', true),
            'allowed_domains' => config('services.oauth.allowed_domains', null),
        ]);
    })->name('providers.status');
    
    // User OAuth info
    Route::get('/user/info', function () {
        $user = auth()->user();
        
        return response()->json([
            'oauth_provider' => $user->oauth_provider,
            'oauth_verified' => $user->oauth_verified,
            'oauth_email' => $user->oauth_email,
            'oauth_domain' => $user->oauth_domain,
            'linked_providers' => $user->oauth_linked_providers ?? [],
            'primary_auth_method' => $user->primary_auth_method,
            'oauth_avatar_url' => $user->oauth_avatar_url,
            'can_link_google' => $user->canLinkOAuthProvider('google'),
            'can_link_microsoft' => $user->canLinkOAuthProvider('microsoft'),
        ]);
    })->name('user.info');
    
    // OAuth activity log for user
    Route::get('/user/activity', function () {
        $user = auth()->user();
        
        $activities = $user->oauthAuditLogs()
                          ->select([
                              'oauth_provider', 
                              'oauth_action', 
                              'status', 
                              'ip_address', 
                              'created_at'
                          ])
                          ->orderBy('created_at', 'desc')
                          ->limit(10)
                          ->get();
        
        return response()->json($activities);
    })->name('user.activity');
    
    // Revoke OAuth tokens (logout from provider)
    Route::post('/revoke/{provider}', function (string $provider) {
        $user = auth()->user();
        
        if ($user->oauth_provider !== $provider) {
            return response()->json(['error' => 'Provider not linked'], 400);
        }
        
        // Clear OAuth tokens (but keep account linked)
        $user->update([
            'oauth_access_token' => null,
            'oauth_refresh_token' => null,
            'oauth_token_expires_at' => null,
        ]);
        
        // Log token revocation
        \App\Models\OAuthAuditLog::create([
            'user_id' => $user->id,
            'oauth_provider' => $provider,
            'oauth_action' => 'tokens.revoked',
            'oauth_event_type' => 'security',
            'status' => 'success',
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
        
        return response()->json(['message' => 'Tokens revoked successfully']);
        
    })->name('revoke')
      ->middleware(['throttle:oauth-revoke']);
});

// ==========================================
// OAUTH WEBHOOK ROUTES (for provider notifications)
// ==========================================

Route::prefix('webhooks/oauth')->name('webhooks.oauth.')->group(function () {
    
    // Google Workspace security notifications
    Route::post('/google/security', function () {
        // Handle Google security notifications
        // This would process events like account compromises, etc.
        return response()->json(['status' => 'received']);
    })->name('google.security');
    
    // Microsoft Graph security notifications  
    Route::post('/microsoft/security', function () {
        // Handle Microsoft security notifications
        return response()->json(['status' => 'received']);
    })->name('microsoft.security');
});

// ==========================================
// OAUTH ADMIN API ROUTES
// ==========================================

Route::prefix('admin/api/oauth')->name('admin.api.oauth.')->middleware(['auth', 'admin', 'api'])->group(function () {
    
    // OAuth statistics
    Route::get('/stats', function () {
        $stats = [
            'total_oauth_users' => \App\Models\User::oAuthUsers()->count(),
            'google_users' => \App\Models\User::byOAuthProvider('google')->count(),
            'microsoft_users' => \App\Models\User::byOAuthProvider('microsoft')->count(),
            'verified_users' => \App\Models\User::verifiedOAuthUsers()->count(),
            'recent_logins' => \App\Models\OAuthAuditLog::forAction('login.attempt')
                                                      ->withStatus('success')
                                                      ->recent(24)
                                                      ->count(),
            'failed_attempts' => \App\Models\OAuthAuditLog::failedAttempts()
                                                         ->recent(24)
                                                         ->count(),
            'security_incidents' => \App\Models\OAuthAuditLog::securityIncidents()
                                                            ->recent(168) // 7 days
                                                            ->count(),
        ];
        
        return response()->json($stats);
    })->name('stats');
    
    // Recent OAuth activity
    Route::get('/recent-activity', function () {
        $activities = \App\Models\OAuthAuditLog::with('user:id,name,email')
                                              ->select([
                                                  'id', 'user_id', 'oauth_provider', 
                                                  'oauth_action', 'status', 'ip_address',
                                                  'security_level', 'created_at'
                                              ])
                                              ->orderBy('created_at', 'desc')
                                              ->limit(50)
                                              ->get();
        
        return response()->json($activities);
    })->name('activity');
    
    // Security incidents requiring review
    Route::get('/security-review', function () {
        $incidents = \App\Models\OAuthAuditLog::requiringReview()
                                             ->with('user:id,name,email')
                                             ->orderBy('created_at', 'desc')
                                             ->get();
        
        return response()->json($incidents);
    })->name('security.review');
    
    // Mark incident as reviewed
    Route::patch('/incidents/{incident}/reviewed', function (\App\Models\OAuthAuditLog $incident) {
        $incident->markAsReviewed();
        
        return response()->json(['message' => 'Incident marked as reviewed']);
    })->name('incidents.reviewed');
});

// ==========================================
// RATE LIMITING DEFINITIONS
// ==========================================

/*
Rate limiting dla OAuth endpoints:
- oauth-redirect: 10 requests per minute per IP
- oauth-callback: 20 requests per minute per IP  
- oauth-verify: 5 requests per minute per IP
- oauth-link: 5 requests per minute per user
- oauth-unlink: 3 requests per minute per user
- oauth-revoke: 2 requests per minute per user
*/