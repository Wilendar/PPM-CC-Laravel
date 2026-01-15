<?php
/**
 * Verify CSS columns exist in product_descriptions table
 */
require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\Schema;

$columns = ['css_rules', 'css_class_map', 'css_mode', 'css_migrated_at'];
$table = 'product_descriptions';

echo "=== Verifying CSS columns in $table ===\n\n";

$allExist = true;
foreach ($columns as $column) {
    $exists = Schema::hasColumn($table, $column);
    echo "$column: " . ($exists ? "OK" : "MISSING") . "\n";
    if (!$exists) $allExist = false;
}

echo "\n" . ($allExist ? "SUCCESS: All columns exist!" : "ERROR: Some columns missing!") . "\n";
