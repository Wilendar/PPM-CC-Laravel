<?php
// Check description CSS mode

require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\ProductDescription;

$desc = ProductDescription::where('product_id', 11183)
    ->where('shop_id', 5)
    ->first();

if (!$desc) {
    echo "Description not found\n";
    exit(1);
}

echo "Description ID: {$desc->id}\n";
echo "CSS Mode: {$desc->css_mode}\n";
echo "CSS Synced At: {$desc->css_synced_at}\n";
echo "CSS Rules count: " . count($desc->css_rules ?? []) . "\n";
echo "Rendered HTML length: " . strlen($desc->rendered_html ?? '') . "\n";

// Check if rendered_html contains style block
$hasStyleBlock = strpos($desc->rendered_html ?? '', '<style>') !== false;
echo "Has <style> block in rendered_html: " . ($hasStyleBlock ? 'Yes' : 'No') . "\n";

// Show first 500 chars of rendered_html
echo "\n--- First 500 chars of rendered_html ---\n";
echo substr($desc->rendered_html ?? '', 0, 500) . "\n";
