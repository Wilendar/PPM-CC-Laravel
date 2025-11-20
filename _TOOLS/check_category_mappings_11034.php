<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\ProductShopData;
use App\Models\Category;
use App\Models\ShopMapping;

echo "\n=== CATEGORY MAPPINGS DIAGNOSTIC (Product 11034, Shop 1) ===\n\n";

// 1. Check product_shop_data.category_mappings
$psd = ProductShopData::where('product_id', 11034)->where('shop_id', 1)->first();

if (!$psd) {
    echo "❌ ProductShopData not found\n";
    exit(1);
}

echo "1. product_shop_data.category_mappings:\n";
echo "   Raw JSON:\n";
echo "   " . ($psd->category_mappings ? json_encode($psd->category_mappings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : 'NULL') . "\n";
echo "\n";

if ($psd->category_mappings) {
    $cm = $psd->category_mappings;
    echo "   Parsed structure:\n";
    echo "   - ui.selected: " . json_encode($cm['ui']['selected'] ?? []) . "\n";
    echo "   - ui.primary: " . ($cm['ui']['primary'] ?? 'null') . "\n";
    echo "   - mappings: " . json_encode($cm['mappings'] ?? []) . "\n";
    echo "\n";
}

// 2. Check Categories table
echo "2. Categories in PPM:\n";
$categories = Category::all();
foreach ($categories as $cat) {
    $parent = $cat->parent_id ? "Parent: {$cat->parent_id}" : "Root";
    echo "   [{$cat->id}] {$cat->name} ({$parent})\n";
}
echo "\n";

// 3. Check ShopMappings
echo "3. Shop Mappings (shop_id=1, type=category):\n";
$mappings = ShopMapping::where('shop_id', 1)
    ->where('mapping_type', 'category')
    ->get();

foreach ($mappings as $mapping) {
    $cat = Category::find($mapping->ppm_value);
    $catName = $cat ? $cat->name : 'N/A';
    echo "   PPM {$mapping->ppm_value} ({$catName}) ↔ PS {$mapping->prestashop_id}\n";
}
echo "\n";

// 4. What PrestaShop sent
echo "4. Checking Laravel logs for PrestaShop API response...\n";
$logFile = storage_path('logs/laravel.log');
if (file_exists($logFile)) {
    $logs = file_get_contents($logFile);

    // Find last pullShopData for product 11034
    $pattern = '/\[ETAP_07b\] Categories converted from PrestaShop format with auto-creation.*?"shop_id":1.*?"prestashop_ids_count":(\d+).*?"ppm_categories_created_mapped":(\d+)/s';

    if (preg_match($pattern, $logs, $match)) {
        echo "   Last conversion:\n";
        echo "   - PrestaShop IDs count: {$match[1]}\n";
        echo "   - PPM categories created/mapped: {$match[2]}\n";
    } else {
        echo "   (No recent conversion log found)\n";
    }
}
echo "\n";

echo "=== END DIAGNOSTIC ===\n\n";
