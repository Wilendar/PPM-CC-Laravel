<?php
/**
 * Check CSS rules in ProductDescription for product 11183, shop 5
 */
require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\ProductDescription;

$pd = ProductDescription::where('product_id', 11183)
    ->where('shop_id', 5)
    ->where('sync_to_prestashop', true)
    ->first();

if ($pd) {
    echo "=== ProductDescription ID: {$pd->id} ===\n";
    echo "css_rules count: " . count($pd->css_rules ?? []) . "\n";
    echo "css_class_map count: " . count($pd->css_class_map ?? []) . "\n";
    echo "updated_at: {$pd->updated_at}\n";

    if (!empty($pd->css_rules)) {
        echo "\nCSS Rules:\n";
        foreach ($pd->css_rules as $selector => $props) {
            echo "  {$selector}: " . json_encode($props) . "\n";
        }
    }

    // Check if rendered_html has UVE classes
    $hasUveClasses = strpos($pd->rendered_html ?? '', 'uve-s') !== false;
    echo "\nrendered_html has UVE classes: " . ($hasUveClasses ? "YES" : "NO") . "\n";
    echo "rendered_html length: " . strlen($pd->rendered_html ?? '') . " chars\n";
} else {
    echo "ProductDescription NOT FOUND!\n";
}
