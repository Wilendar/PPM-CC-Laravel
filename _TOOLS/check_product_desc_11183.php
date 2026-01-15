<?php
/**
 * Check product 11183 description data
 */

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Product;
use App\Models\ProductDescription;

$p = Product::find(11183);

echo "Product 11183:\n";
echo "- SKU: " . ($p->sku ?? 'N/A') . "\n";
echo "- Name: " . ($p->name ?? 'N/A') . "\n";
echo "- long_description length: " . strlen($p->long_description ?? "") . " chars\n";
echo "- short_description length: " . strlen($p->short_description ?? "") . " chars\n";

if ($p->long_description) {
    echo "\n- long_description preview (first 500 chars):\n";
    echo substr($p->long_description, 0, 500) . "\n";
}

echo "\nProductDescription records for product 11183:\n";
$descs = ProductDescription::where('product_id', 11183)->get();

if ($descs->isEmpty()) {
    echo "  NO RECORDS FOUND\n";
} else {
    foreach ($descs as $d) {
        echo "  - shop_id: " . $d->shop_id;
        echo ", blocks_v2: " . (empty($d->blocks_v2) ? "EMPTY" : count($d->blocks_v2) . " blocks");
        echo ", blocks_json: " . (empty($d->blocks_json) ? "EMPTY" : count($d->blocks_json) . " blocks");
        echo ", rendered_html: " . strlen($d->rendered_html ?? "") . " chars\n";
    }
}
