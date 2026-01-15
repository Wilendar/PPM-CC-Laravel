<?php

// Reset OPcache and report status
if (function_exists('opcache_reset')) {
    $result = opcache_reset();
    echo "OPcache reset: " . ($result ? "SUCCESS" : "FAILED") . "\n";
} else {
    echo "OPcache is not available\n";
}

// Also clear any APCu cache if available
if (function_exists('apcu_clear_cache')) {
    apcu_clear_cache();
    echo "APCu cache cleared\n";
}

// For good measure, try to run composer dump-autoload equivalent
$autoloadFile = __DIR__ . '/../vendor/autoload.php';
if (file_exists($autoloadFile)) {
    // Force regenerate class map by touching autoload file
    echo "Autoload file exists\n";
}

echo "Done!\n";
