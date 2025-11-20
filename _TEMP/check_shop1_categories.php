<?php
/**
 * Check Shop ID 1 (Pitbike.pl) category structure
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\PrestaShop\PrestaShopCategoryService;
use App\Models\PrestaShopShop;

echo "\n🔍 Checking Shop ID 1 (Pitbike.pl) categories...\n\n";

$shop = PrestaShopShop::find(1);

if (!$shop) {
    echo "❌ Shop 1 not found\n";
    exit(1);
}

echo "🏪 Shop: {$shop->name} (ID: {$shop->id})\n";
echo "🔗 URL: {$shop->url}\n\n";

$categoryService = app(PrestaShopCategoryService::class);

$categories = $categoryService->fetchCategoriesFromShop($shop);

echo "✅ Fetched " . count($categories) . " categories\n\n";

// Find root categories
$roots = array_filter($categories, fn($c) => in_array($c['id_parent'], [0, 1]));

echo "🌳 ROOT CATEGORIES:\n";
foreach ($roots as $root) {
    echo sprintf("  • [%d] '%s' (parent: %d)\n", $root['id'], $root['name'], $root['id_parent']);
}

echo "\n";

// Find "Wszystko" or "Baza"
$wszystko = array_values(array_filter($categories, fn($c) => stripos($c['name'], 'Wszystko') !== false))[0] ?? null;
$baza = array_values(array_filter($categories, fn($c) => stripos($c['name'], 'Baza') !== false))[0] ?? null;

if ($baza) {
    echo "✅ Found 'Baza': ID {$baza['id']}, parent {$baza['id_parent']}\n";
} else {
    echo "❌ 'Baza' NOT FOUND\n";
}

if ($wszystko) {
    echo "✅ Found 'Wszystko': ID {$wszystko['id']}, parent {$wszystko['id_parent']}\n";
} else {
    echo "❌ 'Wszystko' NOT FOUND\n";
}

echo "\n";

// Show PITGANG path
$pitgang = array_values(array_filter($categories, fn($c) => stripos($c['name'], 'PITGANG') !== false))[0] ?? null;

if ($pitgang) {
    echo "🎯 Found 'PITGANG': ID {$pitgang['id']}, parent {$pitgang['id_parent']}\n\n";

    // Build path
    $path = [];
    $current = $pitgang;

    while ($current) {
        array_unshift($path, "[{$current['id']}] {$current['name']}");

        if ($current['id_parent'] == 0) {
            break;
        }

        $current = array_values(array_filter($categories, fn($c) => $c['id'] == $current['id_parent']))[0] ?? null;
    }

    echo "📍 Full path: " . implode(' → ', $path) . "\n";
}

echo "\n";
