<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== FREQUENCY BUG DIAGNOSIS ===\n\n";

// 1. Check current value in database
$setting = \App\Models\SystemSetting::where('key', 'sync.schedule.frequency')->first();

if ($setting) {
    echo "✅ Setting EXISTS in database:\n";
    echo "   Key: {$setting->key}\n";
    echo "   Value: {$setting->value}\n";
    echo "   Type: {$setting->type}\n";
    echo "   Updated: {$setting->updated_at}\n\n";
} else {
    echo "❌ Setting NOT FOUND in database!\n\n";
}

// 2. Check all sync.schedule.* settings
echo "=== ALL SYNC SCHEDULE SETTINGS ===\n";
$allSettings = \App\Models\SystemSetting::where('key', 'LIKE', 'sync.schedule.%')
    ->orderBy('key')
    ->get();

foreach ($allSettings as $s) {
    echo "  {$s->key} = {$s->value} (type: {$s->type})\n";
}

echo "\n";

// 3. Simulate save operation
echo "=== TESTING SAVE OPERATION ===\n";

try {
    // Test with 'daily' value
    $testValue = 'daily';

    echo "Attempting to save: frequency = '{$testValue}'\n";

    \App\Models\SystemSetting::updateOrCreate(
        ['key' => 'sync.schedule.frequency'],
        [
            'value' => $testValue,
            'type' => 'string',
            'description' => 'TEST: Synchronization frequency',
        ]
    );

    echo "✅ Save completed without errors\n\n";

    // Verify
    $updated = \App\Models\SystemSetting::where('key', 'sync.schedule.frequency')->first();
    echo "Verification after save:\n";
    echo "   Value: {$updated->value}\n";
    echo "   Type: {$updated->type}\n";
    echo "   Updated: {$updated->updated_at}\n\n";

    if ($updated->value === $testValue) {
        echo "✅ VALUE SAVED CORRECTLY!\n";
    } else {
        echo "❌ VALUE MISMATCH! Expected '{$testValue}', got '{$updated->value}'\n";
    }

} catch (\Exception $e) {
    echo "❌ SAVE FAILED: {$e->getMessage()}\n";
    echo "Stack trace:\n{$e->getTraceAsString()}\n";
}

echo "\n=== DIAGNOSIS COMPLETE ===\n";
