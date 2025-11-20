<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== TABLES WITH 'category' ===\n\n";

$tables = DB::select("SHOW TABLES LIKE '%categor%'");

foreach ($tables as $table) {
    $tableName = array_values((array)$table)[0];
    echo "{$tableName}\n";
}

echo "\n=== COMPLETE ===\n";
