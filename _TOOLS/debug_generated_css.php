<?php
// Debug generated CSS for shop 5

require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\ProductDescription;
use App\Services\VisualEditor\CssSyncOrchestrator;

echo "=== GENERATED CSS DEBUG ===\n\n";

$desc = ProductDescription::where('product_id', 11183)
    ->where('shop_id', 5)
    ->first();

if (!$desc) {
    echo "Description not found!\n";
    exit(1);
}

$sync = app(CssSyncOrchestrator::class);

// Use reflection to call protected method
$reflection = new ReflectionClass($sync);
$method = $reflection->getMethod('generateCssFromRulesV2');
$method->setAccessible(true);

$generatedCss = $method->invoke($sync, $desc);

echo "Generated CSS length: " . strlen($generatedCss) . " bytes\n\n";

// Check for NAV TABS FIX
if (str_contains($generatedCss, 'NAV TABS FIX')) {
    echo "NAV TABS FIX: FOUND!\n";

    // Extract the section
    $start = strpos($generatedCss, '/* === NAV TABS FIX');
    $end = strpos($generatedCss, '/* === END NAV TABS FIX ===', $start);
    if ($start !== false && $end !== false) {
        echo "\n" . substr($generatedCss, $start, $end - $start + 30) . "\n";
    }
} else {
    echo "NAV TABS FIX: NOT FOUND!\n";

    // Show first 2000 chars
    echo "\nFirst 2000 chars of generated CSS:\n";
    echo substr($generatedCss, 0, 2000) . "\n";
}
