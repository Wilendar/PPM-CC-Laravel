<?php
/**
 * Check rendered_html for UVE classes
 */
require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\ProductDescription;

$productId = 11183;
$shopId = 5;

echo "=== RENDERED HTML CHECK ===\n";
echo "Product ID: {$productId}\n";
echo "Shop ID: {$shopId}\n\n";

$desc = ProductDescription::where('product_id', $productId)
    ->where('shop_id', $shopId)
    ->first();

if (!$desc) {
    die("ProductDescription not found!\n");
}

$html = $desc->rendered_html ?? '';

echo "=== HTML LENGTH ===\n";
echo strlen($html) . " characters\n\n";

echo "=== SEARCH FOR UVE CLASSES ===\n";
$hasUveClass = strpos($html, 'uve-sa5bca8e8') !== false;
echo "Has uve-sa5bca8e8: " . ($hasUveClass ? "YES" : "NO") . "\n";

$hasAnyUveClass = preg_match('/class="[^"]*uve-[se][a-f0-9]+/', $html);
echo "Has any UVE class: " . ($hasAnyUveClass ? "YES" : "NO") . "\n\n";

echo "=== BUGGY ELEMENT HTML ===\n";
// Extract the Buggy span
if (preg_match('/<span[^>]*data-uve-id="block-0-span-3"[^>]*>[^<]*<\/span>/', $html, $match)) {
    echo $match[0] . "\n";
} else {
    echo "Buggy span not found in rendered_html\n";
}

echo "\n=== CSS CLASS MAP ===\n";
$cssClassMap = $desc->css_class_map ?? [];
print_r($cssClassMap);
