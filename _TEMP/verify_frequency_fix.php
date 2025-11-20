<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== FREQUENCY FIX VERIFICATION ===\n\n";

// Check current value
$setting = \App\Models\SystemSetting::where('key', 'sync.schedule.frequency')->first();

if (!$setting) {
    echo "❌ Setting not found!\n";
    exit(1);
}

echo "Current database value:\n";
echo "  Key: {$setting->key}\n";
echo "  Value: {$setting->value}\n";
echo "  Type: {$setting->type}\n";
echo "  Updated: {$setting->updated_at}\n\n";

// Test save operation
echo "Testing save operation...\n";

$testValue = $setting->value === 'hourly' ? 'daily' : 'hourly';
echo "  Changing: {$setting->value} → {$testValue}\n";

try {
    \App\Models\SystemSetting::updateOrCreate(
        ['key' => 'sync.schedule.frequency'],
        [
            'value' => $testValue,
            'type' => 'string',
            'description' => 'Synchronization frequency',
        ]
    );

    echo "  ✅ Save completed\n\n";

    // Verify
    $updated = \App\Models\SystemSetting::where('key', 'sync.schedule.frequency')->first();
    echo "Verification after save:\n";
    echo "  Value: {$updated->value}\n";
    echo "  Updated: {$updated->updated_at}\n\n";

    if ($updated->value === $testValue) {
        echo "✅ TEST PASSED: Value saved correctly!\n";
        exit(0);
    } else {
        echo "❌ TEST FAILED: Value mismatch!\n";
        echo "   Expected: {$testValue}\n";
        echo "   Got: {$updated->value}\n";
        exit(1);
    }

} catch (\Exception $e) {
    echo "❌ Save failed: {$e->getMessage()}\n";
    exit(1);
}
