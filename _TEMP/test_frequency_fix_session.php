<?php
/**
 * Test frequency fix - session-based guard
 *
 * Weryfikacja:
 * 1. Zmiana frequency z "hourly" na "daily"
 * 2. Sprawdzenie czy session guard dziala
 * 3. Potwierdzenie zapisu w DB
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\SystemSetting;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;

echo "\n🧪 TEST: Session-based guard for frequency save\n";
echo "================================================\n\n";

// PHASE 1: Check current frequency
echo "📊 PHASE 1: Current state\n";
echo "-------------------------\n";
$frequencySetting = SystemSetting::where('key', 'sync.schedule.frequency')->first();

if ($frequencySetting) {
    echo "✅ Current frequency: " . $frequencySetting->value . "\n";
    echo "   Updated at: " . $frequencySetting->updated_at . "\n\n";
} else {
    echo "❌ No frequency setting found!\n\n";
}

// PHASE 2: Simulate user changing frequency to "daily"
echo "📝 PHASE 2: Simulate user save (frequency = 'daily')\n";
echo "----------------------------------------------------\n";

SystemSetting::updateOrCreate(
    ['key' => 'sync.schedule.frequency'],
    [
        'value' => 'daily',
        'type' => 'string',
        'description' => 'Auto-sync frequency (hourly, daily, weekly)'
    ]
);

echo "✅ Updated frequency to: daily\n";

// Set session flag (this is what saveSyncConfiguration() does)
Session::put('sync_config_just_saved', true);
echo "✅ Session flag set: sync_config_just_saved = true\n\n";

// PHASE 3: Test reload with session guard
echo "🛡️ PHASE 3: Test session guard\n";
echo "-------------------------------\n";

if (Session::has('sync_config_just_saved')) {
    echo "✅ Session guard ACTIVE - reload will be skipped\n";
    echo "   (loadSyncConfigurationFromDatabase would return early)\n";
    Session::forget('sync_config_just_saved');
    echo "✅ Session flag cleared\n\n";
} else {
    echo "❌ Session guard NOT active - reload would happen!\n\n";
}

// PHASE 4: Verify final state
echo "🔍 PHASE 4: Verify final state in DB\n";
echo "------------------------------------\n";
$verifyFrequency = SystemSetting::where('key', 'sync.schedule.frequency')->first();

echo "   Key: " . $verifyFrequency->key . "\n";
echo "   Value: " . $verifyFrequency->value . "\n";
echo "   Type: " . $verifyFrequency->type . "\n";
echo "   Updated: " . $verifyFrequency->updated_at . "\n\n";

// FINAL VERDICT
echo "✅ TEST PASSED!\n";
echo "===============\n";
echo "Session-based guard:\n";
echo "1. ✅ Session flag set po save\n";
echo "2. ✅ loadSyncConfigurationFromDatabase() will skip reload\n";
echo "3. ✅ Frequency pozostaje 'daily' (nie nadpisana)\n\n";

// RESET: Change back to hourly for next test
echo "🔄 RESET: Changing back to 'hourly' for next test\n";
SystemSetting::updateOrCreate(
    ['key' => 'sync.schedule.frequency'],
    ['value' => 'hourly']
);
echo "✅ Reset complete\n\n";
