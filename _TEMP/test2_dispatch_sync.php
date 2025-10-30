<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make('Illuminate\Contracts\Console\Kernel');
$kernel->bootstrap();

use App\Models\AttributeType;
use App\Models\PrestaShopShop;
use App\Jobs\PrestaShop\SyncAttributeGroupWithPrestaShop;

echo "=== TEST 2: Export TO PrestaShop ===" . PHP_EOL . PHP_EOL;

// Load AttributeType #1 (Rozmiar)
$attributeType = AttributeType::with('values')->find(1);
if (!$attributeType) {
    echo "ERROR: AttributeType #1 not found!" . PHP_EOL;
    exit(1);
}

echo "AttributeType: " . $attributeType->name . " (ID: " . $attributeType->id . ")" . PHP_EOL;
echo "Display Type: " . $attributeType->display_type . PHP_EOL;
echo "Values: " . $attributeType->values->count() . PHP_EOL;
foreach ($attributeType->values as $value) {
    echo "  - " . $value->name . " (" . $value->color_hex . ")" . PHP_EOL;
}
echo PHP_EOL;

// Load Shop #1 (dev.mpptrade.pl)
$shop = PrestaShopShop::find(1);
if (!$shop) {
    echo "ERROR: Shop #1 not found!" . PHP_EOL;
    exit(1);
}

echo "Target Shop: " . $shop->name . " (ID: " . $shop->id . ")" . PHP_EOL;
echo "Shop URL: " . $shop->url . PHP_EOL;
echo "PrestaShop Version: " . $shop->prestashop_version . PHP_EOL;
echo PHP_EOL;

// Dispatch sync job
echo "Dispatching SyncAttributeGroupWithPrestaShop job..." . PHP_EOL;

try {
    $job = new SyncAttributeGroupWithPrestaShop($attributeType, $shop);
    dispatch($job);

    echo "SUCCESS: Job dispatched to queue!" . PHP_EOL;
    echo PHP_EOL;
    echo "Next steps:" . PHP_EOL;
    echo "1. Check jobs table: SELECT * FROM jobs ORDER BY id DESC LIMIT 1;" . PHP_EOL;
    echo "2. Process queue: php artisan queue:work --once" . PHP_EOL;
    echo "3. Monitor logs: tail -f storage/logs/laravel.log" . PHP_EOL;

} catch (\Exception $e) {
    echo "ERROR: Failed to dispatch job!" . PHP_EOL;
    echo "Exception: " . $e->getMessage() . PHP_EOL;
    echo "Trace:" . PHP_EOL;
    echo $e->getTraceAsString() . PHP_EOL;
    exit(1);
}
