<?php
// Debug CSS path for shop 5

require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\PrestaShopShop;
use App\Services\VisualEditor\PrestaShopCssFetcher;

$shop = PrestaShopShop::find(5);
$cssFiles = $shop->css_files ?? [];

echo "=== SHOP 5 CSS FILES DEBUG ===\n\n";
echo "Total css_files: " . count($cssFiles) . "\n\n";

// Show custom.css entries
echo "=== custom.css entries ===\n";
foreach ($cssFiles as $i => $f) {
    $url = $f['url'] ?? '';
    if (str_contains($url, 'custom.css')) {
        echo "[$i] name: " . ($f['name'] ?? 'N/A') . "\n";
        echo "    url: $url\n";
        echo "    in themes: " . (str_contains($url, '/themes/') ? 'YES' : 'NO') . "\n\n";
    }
}

// Check getDefaultCssPath via reflection
$fetcher = app(PrestaShopCssFetcher::class);
$reflection = new ReflectionClass($fetcher);
$method = $reflection->getMethod('getDefaultCssPath');
$method->setAccessible(true);
$defaultPath = $method->invoke($fetcher, $shop);

echo "=== getDefaultCssPath result ===\n";
echo "Path: " . ($defaultPath ?? 'NULL') . "\n";
