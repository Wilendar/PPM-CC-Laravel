<?php

/**
 * Clear PHP Opcache
 *
 * Quick script to reset opcache after deployment
 */

echo "=== OPCACHE CLEAR ===\n\n";

if (function_exists('opcache_reset')) {
    if (opcache_reset()) {
        echo "✅ Opcache cleared successfully!\n";

        // Show opcache status
        $status = opcache_get_status();
        if ($status) {
            echo "\nOpcache Status:\n";
            echo "  - Memory used: " . number_format($status['memory_usage']['used_memory'] / 1024 / 1024, 2) . " MB\n";
            echo "  - Cached scripts: " . $status['opcache_statistics']['num_cached_scripts'] . "\n";
            echo "  - Hits: " . number_format($status['opcache_statistics']['hits']) . "\n";
            echo "  - Misses: " . number_format($status['opcache_statistics']['misses']) . "\n";
        }
    } else {
        echo "❌ Failed to clear opcache\n";
    }
} else {
    echo "⚠️  Opcache not available or not enabled\n";
}

echo "\n=== END ===\n";
