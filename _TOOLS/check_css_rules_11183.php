<?php
/**
 * Check CSS rules in ProductDescription for product 11183 / shop 5
 */
require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\ProductDescription;

$productId = 11183;
$shopId = 5;

echo "=== CSS RULES CHECK ===\n";
echo "Product ID: {$productId}\n";
echo "Shop ID: {$shopId}\n\n";

$desc = ProductDescription::where('product_id', $productId)
    ->where('shop_id', $shopId)
    ->first();

if (!$desc) {
    die("ProductDescription not found!\n");
}

echo "=== CSS CLASS MAP ===\n";
$cssClassMap = $desc->css_class_map ?? [];
if (empty($cssClassMap)) {
    echo "EMPTY!\n";
} else {
    foreach ($cssClassMap as $elementId => $className) {
        echo "  {$elementId} => {$className}\n";
    }
}

echo "\n=== CSS RULES ===\n";
$cssRules = $desc->css_rules ?? [];
if (empty($cssRules)) {
    echo "EMPTY!\n";
} else {
    foreach ($cssRules as $selector => $rules) {
        echo "\n{$selector}:\n";
        foreach ($rules as $prop => $value) {
            $highlight = (stripos($prop, 'decoration') !== false) ? ' <-- TEXT DECORATION!' : '';
            echo "  {$prop}: {$value}{$highlight}\n";
        }
    }
}

echo "\n=== SEARCH FOR text-decoration ===\n";
$found = false;
foreach ($cssRules as $selector => $rules) {
    if (isset($rules['text-decoration'])) {
        echo "FOUND: {$selector} has text-decoration: {$rules['text-decoration']}\n";
        $found = true;
    }
}
if (!$found) {
    echo "NOT FOUND - text-decoration is NOT in any CSS rule!\n";
}

echo "\n=== CSS MODE ===\n";
echo "css_mode: " . ($desc->css_mode ?? 'null') . "\n";
echo "css_synced_at: " . ($desc->css_synced_at ?? 'null') . "\n";
