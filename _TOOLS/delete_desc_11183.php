<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$deleted = App\Models\ProductDescription::where('product_id', 11183)
    ->where('shop_id', 5)
    ->delete();

echo "Deleted: {$deleted} records\n";
