<?php
/**
 * Fix Schedule Frequency
 * Change from hourly to every_six_hours
 */

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\SystemSetting;

echo "=== FIX SCHEDULE FREQUENCY ===\n\n";

echo "BEFORE:\n";
echo "  sync.schedule.frequency: " . SystemSetting::get('sync.schedule.frequency', 'NOT SET') . "\n";

// Update to every_six_hours
SystemSetting::set('sync.schedule.frequency', 'every_six_hours');

echo "\nAFTER:\n";
echo "  sync.schedule.frequency: " . SystemSetting::get('sync.schedule.frequency', 'NOT SET') . "\n";

// Build new cron expression
$frequency = SystemSetting::get('sync.schedule.frequency', 'every_six_hours');
switch ($frequency) {
    case 'hourly':
        $cron = '0 * * * *';
        break;
    case 'every_six_hours':
    default:
        $cron = '0 */6 * * *';
        break;
}

echo "  New cron expression: {$cron}\n";

echo "\n=== DONE ===\n";
