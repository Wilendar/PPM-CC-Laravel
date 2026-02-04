<?php
/**
 * Subiekt GT REST API Wrapper - Example Configuration
 *
 * Copy this file to config.php and adjust values for your environment.
 *
 * SECURITY CHECKLIST:
 * 1. Change all API keys to unique, secure values
 * 2. Set database password
 * 3. Configure IP whitelist for production
 * 4. Ensure config.php is NOT in version control
 *
 * @package SubiektGT_REST_Wrapper
 * @version 1.0.0
 */

return [
    // ==========================================
    // DATABASE CONNECTION (SQL Server)
    // ==========================================
    'database' => [
        // SQL Server connection string components
        'host' => '(local)\INSERTGT',     // SQL Server instance name
        'port' => '1433',                  // Default SQL Server port
        'database' => 'YOUR_FIRMA_NAME',  // Subiekt GT database name (e.g., 'MPP_TRADE')
        'username' => 'sa',                // SQL Server username
        'password' => 'YOUR_PASSWORD',     // SQL Server password - CHANGE THIS!

        // Connection options
        'charset' => 'UTF-8',
        'trust_certificate' => true,       // Trust self-signed certificates
        'connection_timeout' => 10,        // Connection timeout in seconds
        'query_timeout' => 30,             // Query timeout in seconds
    ],

    // ==========================================
    // API SECURITY
    // ==========================================
    'api' => [
        // API Keys - CHANGE THESE TO SECURE RANDOM STRINGS!
        // Generate with: bin2hex(random_bytes(32))
        'keys' => [
            // Production key with full access
            'CHANGE_ME_TO_SECURE_64_CHAR_HEX_STRING_FOR_PRODUCTION' => [
                'name' => 'PPM Production',
                'permissions' => ['read', 'write'],
                'ip_whitelist' => [
                    // Add Hostido server IP here for security
                    // '123.456.789.xxx',
                ],
            ],
            // Read-only key for monitoring
            'CHANGE_ME_TO_SECURE_64_CHAR_HEX_STRING_FOR_READONLY' => [
                'name' => 'PPM Read-Only',
                'permissions' => ['read'],
                'ip_whitelist' => [],
            ],
        ],

        // Rate limiting
        'rate_limit' => [
            'enabled' => true,
            'requests_per_minute' => 60,
            'storage' => 'file',
            'storage_path' => __DIR__ . '/storage/rate_limits/',
        ],

        // Request settings
        'max_page_size' => 500,
        'default_page_size' => 100,

        // CORS settings
        'cors' => [
            'enabled' => true,
            'allowed_origins' => ['https://ppm.mpptrade.pl'], // Production domain
            'allowed_methods' => ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
            'allowed_headers' => ['Content-Type', 'X-API-Key', 'Authorization'],
        ],
    ],

    // ==========================================
    // LOGGING
    // ==========================================
    'logging' => [
        'enabled' => true,
        'path' => __DIR__ . '/storage/logs/',
        'level' => 'info',
        'max_files' => 30,
        'log_requests' => true,
        'log_responses' => false,
        'log_errors' => true,
    ],

    // ==========================================
    // CACHE
    // ==========================================
    'cache' => [
        'enabled' => false,
        'driver' => 'file',
        'path' => __DIR__ . '/storage/cache/',
        'ttl' => 300,
    ],

    // ==========================================
    // DEFAULTS
    // ==========================================
    'defaults' => [
        'price_type_id' => 1,
        'warehouse_id' => 1,
    ],
];
