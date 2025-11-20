<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make('Illuminate\Contracts\Console\Kernel');
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

echo "\n=== CHECK VALIDATION COLUMNS ===\n";

try {
    // Check if columns exist
    $hasValidationWarnings = Schema::hasColumn('product_shop_data', 'validation_warnings');
    $hasValidationFlag = Schema::hasColumn('product_shop_data', 'has_validation_warnings');
    $hasValidationChecked = Schema::hasColumn('product_shop_data', 'validation_checked_at');

    echo "\nValidation columns status:\n";
    echo "  validation_warnings: " . ($hasValidationWarnings ? '✓ EXISTS' : '✗ MISSING') . "\n";
    echo "  has_validation_warnings: " . ($hasValidationFlag ? '✓ EXISTS' : '✗ MISSING') . "\n";
    echo "  validation_checked_at: " . ($hasValidationChecked ? '✓ EXISTS' : '✗ MISSING') . "\n";

    if (!$hasValidationWarnings || !$hasValidationFlag || !$hasValidationChecked) {
        echo "\n⚠️ Columns missing - need to run migration!\n";

        // Run migration SQL directly
        echo "\nRunning migration SQL...\n";

        DB::statement("
            ALTER TABLE product_shop_data
            ADD COLUMN validation_warnings JSON NULL AFTER conflict_log,
            ADD COLUMN has_validation_warnings BOOLEAN DEFAULT FALSE AFTER validation_warnings,
            ADD COLUMN validation_checked_at TIMESTAMP NULL AFTER has_validation_warnings
        ");

        echo "✓ Migration SQL executed successfully!\n";

        // Verify again
        $hasValidationWarnings = Schema::hasColumn('product_shop_data', 'validation_warnings');
        $hasValidationFlag = Schema::hasColumn('product_shop_data', 'has_validation_warnings');
        $hasValidationChecked = Schema::hasColumn('product_shop_data', 'validation_checked_at');

        echo "\nVerification after migration:\n";
        echo "  validation_warnings: " . ($hasValidationWarnings ? '✓ EXISTS' : '✗ MISSING') . "\n";
        echo "  has_validation_warnings: " . ($hasValidationFlag ? '✓ EXISTS' : '✗ MISSING') . "\n";
        echo "  validation_checked_at: " . ($hasValidationChecked ? '✓ EXISTS' : '✗ MISSING') . "\n";
    } else {
        echo "\n✓ All validation columns exist!\n";
    }

} catch (\Exception $e) {
    echo "\nERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}

echo "\n=== DONE ===\n";
