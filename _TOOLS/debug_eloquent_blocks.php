<?php

/**
 * Debug Eloquent model loading of blocks_json
 */

require_once __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\ProductDescription;

// Load via Eloquent (with casts)
$desc = ProductDescription::where('product_id', 11183)
    ->where('shop_id', 5)
    ->first();

echo "=== ELOQUENT MODEL ===\n";
echo "Record ID: " . ($desc->id ?? 'NULL') . "\n";
echo "blocks_json TYPE: " . gettype($desc->blocks_json) . "\n";
echo "blocks_json IS_ARRAY: " . (is_array($desc->blocks_json) ? 'YES' : 'NO') . "\n";
echo "blocks_json COUNT: " . count($desc->blocks_json ?? []) . "\n";
echo "blocks_json EMPTY: " . (empty($desc->blocks_json) ? 'YES' : 'NO') . "\n";
echo "!empty(blocks_json): " . (!empty($desc->blocks_json) ? 'TRUE' : 'FALSE') . "\n\n";

echo "=== CONDITION CHECK ===\n";
if ($desc && !empty($desc->blocks_json)) {
    echo "CONDITION: TRUE -> Would load from ProductDescription (NOT fallback)\n";
} else {
    echo "CONDITION: FALSE -> Would fall through to loadFromProductDescription()\n";
}

echo "\n=== RAW blocks_json VALUE ===\n";
var_dump($desc->blocks_json);

// Check raw attributes
echo "\n=== RAW ATTRIBUTE (before cast) ===\n";
$rawAttrs = $desc->getRawOriginal('blocks_json');
echo "Raw type: " . gettype($rawAttrs) . "\n";
echo "Raw value: " . $rawAttrs . "\n";
