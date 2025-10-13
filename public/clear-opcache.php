<?php
/**
 * Clear PHP Opcache - Emergency Helper
 *
 * Access: https://ppm.mpptrade.pl/clear-opcache.php
 *
 * SECURITY: Delete this file after use!
 */

header('Content-Type: text/plain');

if (function_exists('opcache_reset')) {
    if (opcache_reset()) {
        echo "✅ Opcache cleared successfully!\n";
        echo "Timestamp: " . date('Y-m-d H:i:s') . "\n";
    } else {
        echo "❌ Failed to clear opcache\n";
    }
} else {
    echo "⚠️ Opcache not enabled\n";
}

if (function_exists('opcache_get_status')) {
    $status = opcache_get_status(false);
    echo "\nOpcache Status:\n";
    echo "- Enabled: " . ($status['opcache_enabled'] ? 'Yes' : 'No') . "\n";
    echo "- Cache Full: " . ($status['cache_full'] ? 'Yes' : 'No') . "\n";
    echo "- Restart Pending: " . ($status['restart_pending'] ? 'Yes' : 'No') . "\n";
}

echo "\n⚠️ SECURITY: DELETE THIS FILE AFTER USE!\n";
echo "Run: rm /home/host379076/domains/ppm.mpptrade.pl/public_html/public/clear-opcache.php\n";
