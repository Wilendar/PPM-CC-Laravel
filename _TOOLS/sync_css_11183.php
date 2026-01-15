<?php
// Sync CSS for product 11183 shop 5

require_once __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\ProductDescription;
use App\Services\VisualEditor\CssSyncOrchestrator;

$desc = ProductDescription::where('product_id', 11183)
    ->where('shop_id', 5)
    ->first();

if (!$desc) {
    echo "Description not found\n";
    exit(1);
}

echo "Found description ID: {$desc->id}\n";
echo "CSS Rules count: " . count($desc->css_rules ?? []) . "\n";

$sync = app(CssSyncOrchestrator::class);
$result = $sync->syncProductDescription($desc, true);

echo "Sync result:\n";
print_r($result);
