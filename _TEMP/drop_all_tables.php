<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

DB::statement('SET FOREIGN_KEY_CHECKS=0');

$tables = collect(DB::select('SHOW TABLES'))->map(function ($table) {
    return array_values((array) $table)[0];
});

echo "Found " . $tables->count() . " tables to drop:\n";
foreach ($tables as $table) {
    echo "  Dropping: {$table}\n";
    DB::statement("DROP TABLE IF EXISTS {$table}");
}

DB::statement('SET FOREIGN_KEY_CHECKS=1');
echo "\nAll tables dropped successfully!\n";
