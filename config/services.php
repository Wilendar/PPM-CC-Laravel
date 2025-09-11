<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | PPM-CC-Laravel OAuth2 Configuration
    | FAZA D: OAuth2 + Advanced Features
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    /*
    |--------------------------------------------------------------------------
    | OAuth2 Providers Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration dla OAuth2 integration z Google Workspace i Microsoft Entra ID
    | Obsługuje also generic OAuth2 providers dla future extensions
    |
    */

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI', env('APP_URL') . '/auth/google/callback'),
        
        // Google Workspace specific settings
        'approval_prompt' => 'auto',
        'access_type' => 'offline', // For refresh tokens
        'include_granted_scopes' => true,
        
        // Scopes for Google Workspace
        'scopes' => [
            'openid',
            'profile',
            'email',
            // 'https://www.googleapis.com/auth/admin.directory.user.readonly', // For admin consent
        ],
        
        // Domain restriction dla workspace
        'hosted_domain' => env('GOOGLE_HOSTED_DOMAIN', null), // e.g., 'mpptrade.pl'
        'require_verification' => env('GOOGLE_REQUIRE_VERIFICATION', true),
    ],

    'microsoft' => [
        'client_id' => env('MICROSOFT_CLIENT_ID'),
        'client_secret' => env('MICROSOFT_CLIENT_SECRET'),
        'redirect' => env('MICROSOFT_REDIRECT_URI', env('APP_URL') . '/auth/microsoft/callback'),
        
        // Microsoft Entra ID specific settings
        'tenant' => env('MICROSOFT_TENANT_ID', 'common'), // Use 'common' for multi-tenant or specific tenant ID
        'resource' => env('MICROSOFT_RESOURCE', 'https://graph.microsoft.com'),
        
        // Scopes for Microsoft Graph
        'scopes' => [
            'openid',
            'profile',
            'email',
            'User.Read',
            // 'Directory.AccessAsUser.All', // For advanced directory access
        ],
        
        // Domain restriction
        'allowed_domains' => env('MICROSOFT_ALLOWED_DOMAINS', null), // Comma-separated domains
        'require_verification' => env('MICROSOFT_REQUIRE_VERIFICATION', true),
    ],

    // Generic OAuth configuration
    'oauth' => [
        // Global OAuth settings
        'enabled_providers' => env('OAUTH_ENABLED_PROVIDERS', 'google,microsoft'),
        'allowed_domains' => env('OAUTH_ALLOWED_DOMAINS', null), // Comma-separated domains
        'auto_registration' => env('OAUTH_AUTO_REGISTRATION', true),
        'link_existing_accounts' => env('OAUTH_LINK_EXISTING', true),
        
        // Security settings
        'token_expiry_buffer' => env('OAUTH_TOKEN_EXPIRY_BUFFER', 300), // 5 minutes buffer
        'max_login_attempts' => env('OAUTH_MAX_ATTEMPTS', 5),
        'lockout_duration' => env('OAUTH_LOCKOUT_MINUTES', 30),
        
        // Session settings
        'remember_oauth_sessions' => env('OAUTH_REMEMBER_SESSIONS', true),
        'session_lifetime' => env('OAUTH_SESSION_LIFETIME', 120), // minutes
        
        // Avatar and profile sync
        'sync_avatars' => env('OAUTH_SYNC_AVATARS', true),
        'sync_profile_data' => env('OAUTH_SYNC_PROFILE', true),
        'profile_update_frequency' => env('OAUTH_PROFILE_UPDATE_HOURS', 24), // hours
    ],

    /*
    |--------------------------------------------------------------------------
    | ERP Integration Services
    |--------------------------------------------------------------------------
    |
    | Configuration dla external ERP systems (Baselinker, Subiekt GT, etc.)
    | These are separate from OAuth but related to external integrations
    |
    */

    'baselinker' => [
        'api_token' => env('BASELINKER_API_TOKEN'),
        'api_url' => env('BASELINKER_API_URL', 'https://api.baselinker.com/connector.php'),
        'timeout' => env('BASELINKER_TIMEOUT', 30),
    ],

    'subiekt_gt' => [
        'api_url' => env('SUBIEKT_GT_API_URL'),
        'username' => env('SUBIEKT_GT_USERNAME'),
        'password' => env('SUBIEKT_GT_PASSWORD'),
        'database' => env('SUBIEKT_GT_DATABASE'),
        'timeout' => env('SUBIEKT_GT_TIMEOUT', 30),
    ],

    /*
    |--------------------------------------------------------------------------
    | Prestashop Integration
    |--------------------------------------------------------------------------
    |
    | Configuration dla multiple Prestashop instances
    | Each shop has its own API credentials
    |
    */

    'prestashop' => [
        'default_timeout' => env('PRESTASHOP_DEFAULT_TIMEOUT', 30),
        'max_concurrent_requests' => env('PRESTASHOP_MAX_CONCURRENT', 5),
        
        // Shops configuration will be loaded from database
        // This is just default settings
        'default_settings' => [
            'api_version' => '1.7',
            'image_quality' => 90,
            'max_image_size' => '2048x2048',
            'chunk_size' => 100, // For bulk operations
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | File Storage Services
    |--------------------------------------------------------------------------
    |
    | Configuration dla file uploads and media storage
    |
    */

    'storage' => [
        'max_file_size' => env('MAX_FILE_SIZE', 10 * 1024 * 1024), // 10MB
        'allowed_image_types' => ['jpg', 'jpeg', 'png', 'gif', 'webp'],
        'allowed_document_types' => ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'txt', 'zip'],
        'image_optimization' => env('OPTIMIZE_IMAGES', true),
        'backup_to_cloud' => env('BACKUP_TO_CLOUD', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Notification Services
    |--------------------------------------------------------------------------
    |
    | Configuration dla notifications (email, SMS, push, etc.)
    |
    */

    'notifications' => [
        'mail' => [
            'from_address' => env('MAIL_FROM_ADDRESS', 'noreply@ppm.mpptrade.pl'),
            'from_name' => env('MAIL_FROM_NAME', 'PPM System'),
        ],
        
        'sms' => [
            'provider' => env('SMS_PROVIDER', null), // 'smsapi', 'twilio', etc.
            'api_key' => env('SMS_API_KEY'),
            'sender_name' => env('SMS_SENDER', 'PPM'),
        ],
        
        'push' => [
            'fcm_server_key' => env('FCM_SERVER_KEY'),
            'vapid_public_key' => env('VAPID_PUBLIC_KEY'),
            'vapid_private_key' => env('VAPID_PRIVATE_KEY'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache and Performance
    |--------------------------------------------------------------------------
    |
    | Configuration dla caching and performance optimization
    |
    */

    'cache' => [
        'oauth_token_cache_ttl' => env('CACHE_OAUTH_TOKENS_TTL', 3600), // 1 hour
        'user_profile_cache_ttl' => env('CACHE_USER_PROFILE_TTL', 1800), // 30 minutes  
        'api_response_cache_ttl' => env('CACHE_API_RESPONSE_TTL', 300), // 5 minutes
        'search_results_cache_ttl' => env('CACHE_SEARCH_RESULTS_TTL', 600), // 10 minutes
    ],

];