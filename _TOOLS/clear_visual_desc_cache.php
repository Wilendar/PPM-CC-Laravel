<?php

/**
 * Clear visual description cache for testing re-import from "Opisy i SEO"
 * Run: php _TOOLS/clear_visual_desc_cache.php [product_id] [shop_id]
 */

require_once __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\ProductDescription;

$productId = $argv[1] ?? 11183;
$shopId = $argv[2] ?? 5;

echo "Clearing ProductDescription for product_id={$productId}, shop_id={$shopId}\n";

$deleted = ProductDescription::where('product_id', $productId)
    ->where('shop_id', $shopId)
    ->delete();

echo "Deleted {$deleted} record(s).\n";
echo "Now visual editor will re-import from 'Opisy i SEO' tab.\n";
