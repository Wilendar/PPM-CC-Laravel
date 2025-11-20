<?php

/**
 * Automated Frequency Bug Test - NO UI REQUIRED
 * Tests if frequency value persists after save
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\SystemSetting;
use Illuminate\Support\Facades\Log;

echo "=== AUTOMATED FREQUENCY BUG TEST ===\n\n";

// Clear logs
file_put_contents(storage_path('logs/laravel.log'), '');
echo "✓ Logs cleared\n\n";

// Step 1: Get current value
echo "1. Current value in database:\n";
$currentSetting = SystemSetting::where('key', 'sync.schedule.frequency')->first();
if ($currentSetting) {
    echo "   Value: {$currentSetting->value}\n";
    echo "   Updated: {$currentSetting->updated_at}\n\n";
} else {
    echo "   ❌ Setting not found!\n\n";
    exit(1);
}

// Step 2: Change to different value (simulate user save)
$originalValue = $currentSetting->value;
$newValue = $originalValue === 'hourly' ? 'daily' : 'hourly';

echo "2. Simulating user save (changing: {$originalValue} → {$newValue})...\n";
Log::info('[AUTO TEST] Simulating user save', [
    'original' => $originalValue,
    'new' => $newValue,
]);

SystemSetting::updateOrCreate(
    ['key' => 'sync.schedule.frequency'],
    [
        'value' => $newValue,
        'type' => 'string',
        'description' => 'Synchronization frequency',
    ]
);

// Verify immediately after save
$afterSave = SystemSetting::where('key', 'sync.schedule.frequency')->first();
echo "   After save: {$afterSave->value}\n";
echo "   Updated: {$afterSave->updated_at}\n\n";

if ($afterSave->value !== $newValue) {
    echo "   ❌ VALUE CHANGED IMMEDIATELY AFTER SAVE!\n";
    echo "      Expected: {$newValue}, Got: {$afterSave->value}\n\n";
    exit(1);
}

// Step 3: Simulate Livewire reload (fresh query from DB)
echo "3. Simulating Livewire component reload (fresh query)...\n";
Log::info('[AUTO TEST] Simulating component reload');

// Clear all caches that might affect query
\Illuminate\Support\Facades\Cache::flush();
\Illuminate\Support\Facades\DB::purge();
\Illuminate\Support\Facades\DB::reconnect();

// Fresh query (like Livewire would do)
$afterReload = SystemSetting::where('key', 'sync.schedule.frequency')->first();
echo "   After reload: {$afterReload->value}\n";
echo "   Updated: {$afterReload->updated_at}\n\n";

// Step 4: Check if value persisted
if ($afterReload->value === $newValue) {
    echo "✅ TEST PASSED: Value persisted after reload!\n";
    echo "   Saved: {$newValue}\n";
    echo "   After reload: {$afterReload->value}\n\n";
    $exitCode = 0;
} else {
    echo "❌ TEST FAILED: Value REVERTED after reload!\n";
    echo "   Expected: {$newValue}\n";
    echo "   Got: {$afterReload->value}\n\n";
    $exitCode = 1;
}

// Step 5: Restore original value
echo "4. Restoring original value ({$originalValue})...\n";
SystemSetting::where('key', 'sync.schedule.frequency')->update([
    'value' => $originalValue,
]);
echo "   ✓ Restored\n\n";

// Step 6: Show relevant logs
echo "5. Relevant logs:\n";
echo str_repeat('=', 80) . "\n";
$logs = file_get_contents(storage_path('logs/laravel.log'));
$relevantLines = array_filter(
    explode("\n", $logs),
    fn($line) => stripos($line, 'AUTO TEST') !== false
        || stripos($line, 'sync.schedule.frequency') !== false
        || stripos($line, 'saveSyncConfiguration') !== false
);
foreach ($relevantLines as $line) {
    echo $line . "\n";
}
echo str_repeat('=', 80) . "\n\n";

echo "=== TEST COMPLETE ===\n";
exit($exitCode);
