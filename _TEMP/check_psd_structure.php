<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "\n=== STRUKTURA product_shop_data ===\n\n";

$columns = DB::select("SHOW COLUMNS FROM product_shop_data");

foreach ($columns as $col) {
    printf("%-30s %-30s %s\n",
        $col->Field,
        $col->Type,
        $col->Null == 'YES' ? 'NULL' : 'NOT NULL'
    );
}

echo "\n=== PRZYKŁADOWE REKORDY ===\n\n";

$records = DB::table('product_shop_data')
    ->limit(3)
    ->get();

foreach ($records as $rec) {
    echo json_encode($rec, JSON_PRETTY_PRINT) . "\n\n";
}

echo "\n";
