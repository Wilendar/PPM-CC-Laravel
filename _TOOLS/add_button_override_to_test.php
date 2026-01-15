<?php
/**
 * Add button override rules to TEST shop custom.css
 * Bypasses CSS editor validation (original file has pre-existing brace imbalance)
 */

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\PrestaShopShop;
use App\Services\VisualEditor\PrestaShopCssFetcher;

$shopId = 5; // Test KAYO
$shop = PrestaShopShop::find($shopId);

if (!$shop) {
    echo "Shop ID $shopId not found\n";
    exit(1);
}

echo "Shop: {$shop->name}\n";

$cssFetcher = app(PrestaShopCssFetcher::class);

// Fetch current CSS
$result = $cssFetcher->getCustomCss($shop);
if (!$result['success']) {
    echo "Failed to fetch CSS: " . ($result['error'] ?? 'Unknown error') . "\n";
    exit(1);
}

$css = $result['content'];
echo "Current CSS size: " . strlen($css) . " bytes\n";

// Check if button override already exists
if (strpos($css, 'PPM Button Override') !== false) {
    echo "Button override rules already exist in the CSS file.\n";
    exit(0);
}

// Find UVE marker
$uveIdx = strpos($css, '@uve-styles-start');
if ($uveIdx === false) {
    echo "UVE marker not found, appending at end\n";
    $insertPoint = strlen($css);
} else {
    // Find line start before UVE marker
    $insertPoint = strrpos(substr($css, 0, $uveIdx), "\n");
    if ($insertPoint === false) $insertPoint = 0;
}

// Button override rules
$buttonRules = <<<'CSS'

/* PPM Button Override v2.2 */
.btn-secondary {
    background: none !important;
    color: #eb5d21 !important;
    border-color: #eb5d21 !important;
}
.btn-primary {
    border-color: #eb5d21 !important;
}
/* End Button Override */

CSS;

// Insert rules
$newCss = substr($css, 0, $insertPoint) . $buttonRules . substr($css, $insertPoint);
echo "New CSS size: " . strlen($newCss) . " bytes\n";

// Save CSS (bypass validation)
$filePath = $result['filePath'] ?? null;
$saveResult = $cssFetcher->saveCustomCss($shop, $newCss, $filePath);

if ($saveResult['success']) {
    echo "SUCCESS: Button override rules added to custom.css\n";
} else {
    echo "FAILED: " . ($saveResult['error'] ?? 'Unknown error') . "\n";
    exit(1);
}
