<?php
// Debug css_files structure for shop 5

require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\PrestaShopShop;

$shop = PrestaShopShop::find(5);
$cssFiles = $shop->css_files ?? [];

echo "=== CSS FILES STRUCTURE DEBUG ===\n\n";

// Find custom.css from themes
foreach ($cssFiles as $i => $file) {
    $url = $file['url'] ?? '';

    // Only show files that contain 'custom' or 'theme'
    if (str_contains(strtolower($url), 'custom.css') || ($i === 0)) {
        echo "--- File [{$i}] ---\n";
        echo "  Keys: " . implode(', ', array_keys($file)) . "\n";
        foreach ($file as $key => $value) {
            if ($key !== 'cached_content') { // Skip large content
                echo "  {$key}: " . (is_bool($value) ? ($value ? 'true' : 'false') : $value) . "\n";
            }
        }
        echo "\n";
    }
}

// Test getDefaultCssPath logic
echo "=== TESTING getDefaultCssPath LOGIC ===\n\n";

$path = null;

// Priority 1: Look for THEME custom.css
foreach ($cssFiles as $file) {
    $filename = strtolower($file['filename'] ?? '');
    $url = $file['url'] ?? '';

    echo "Checking: filename='{$filename}', url contains /themes/: " . (str_contains($url, '/themes/') ? 'YES' : 'NO') . "\n";

    if ($filename === 'custom.css' && str_contains($url, '/themes/')) {
        echo "MATCH FOUND!\n";
        $path = $url; // simplified
        break;
    }
}

if ($path) {
    echo "\nPriority 1 found: {$path}\n";
} else {
    echo "\nPriority 1 NOT found - will fallback\n";
}
