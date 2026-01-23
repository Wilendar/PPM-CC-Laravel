<?php

require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Warehouse;
use App\Models\PriceGroup;

echo "=== Warehouses ===" . PHP_EOL;
echo "Total: " . Warehouse::count() . PHP_EOL;
echo "With erp_mapping (not null): " . Warehouse::whereNotNull('erp_mapping')->count() . PHP_EOL;

$withMapping = Warehouse::whereNotNull('erp_mapping')->get();
foreach ($withMapping as $w) {
    echo "  ID {$w->id}: {$w->name}" . PHP_EOL;
    echo "    erp_mapping: " . json_encode($w->erp_mapping) . PHP_EOL;
}

// Check with JSON_EXTRACT
$withSubiekt = Warehouse::whereRaw("JSON_EXTRACT(erp_mapping, '$.subiekt_gt') IS NOT NULL")->get();
echo PHP_EOL . "With subiekt_gt mapping (JSON_EXTRACT): " . $withSubiekt->count() . PHP_EOL;

echo PHP_EOL . "=== Price Groups ===" . PHP_EOL;
echo "Total: " . PriceGroup::count() . PHP_EOL;
echo "With erp_mapping (not null): " . PriceGroup::whereNotNull('erp_mapping')->count() . PHP_EOL;

$withMapping = PriceGroup::whereNotNull('erp_mapping')->get();
foreach ($withMapping as $pg) {
    echo "  ID {$pg->id}: {$pg->name}" . PHP_EOL;
    echo "    erp_mapping: " . json_encode($pg->erp_mapping) . PHP_EOL;
}

// Check with JSON_EXTRACT
$withSubiekt = PriceGroup::whereRaw("JSON_EXTRACT(erp_mapping, '$.subiekt_gt') IS NOT NULL")->get();
echo PHP_EOL . "With subiekt_gt mapping (JSON_EXTRACT): " . $withSubiekt->count() . PHP_EOL;
