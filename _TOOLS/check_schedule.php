<?php
/**
 * Check Schedule Configuration
 */

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\SystemSetting;

echo "=== SCHEDULE CONFIGURATION ===\n\n";

echo "1. SYNC SCHEDULE SETTINGS:\n";
$settings = [
    'sync.schedule.enabled' => SystemSetting::get('sync.schedule.enabled', true),
    'sync.schedule.frequency' => SystemSetting::get('sync.schedule.frequency', 'every_six_hours'),
    'sync.schedule.hour' => SystemSetting::get('sync.schedule.hour', 2),
    'sync.schedule.days_of_week' => SystemSetting::get('sync.schedule.days_of_week', ['monday', 'tuesday', 'wednesday', 'thursday', 'friday']),
    'sync.schedule.only_connected' => SystemSetting::get('sync.schedule.only_connected', true),
    'sync.schedule.skip_maintenance' => SystemSetting::get('sync.schedule.skip_maintenance', true),
];

foreach ($settings as $key => $value) {
    if (is_array($value)) {
        echo "  {$key}: [" . implode(', ', $value) . "]\n";
    } else {
        echo "  {$key}: " . ($value === true ? 'true' : ($value === false ? 'false' : $value)) . "\n";
    }
}

echo "\n2. CRON EXPRESSION:\n";
// Build cron expression like in console.php
$frequency = SystemSetting::get('sync.schedule.frequency', 'every_six_hours');
$hour = SystemSetting::get('sync.schedule.hour', 2);

switch ($frequency) {
    case 'hourly':
        $cron = '0 * * * *';
        break;
    case 'daily':
        $cron = "0 {$hour} * * *";
        break;
    case 'weekly':
        $dayMap = ['sunday' => 0, 'monday' => 1, 'tuesday' => 2, 'wednesday' => 3, 'thursday' => 4, 'friday' => 5, 'saturday' => 6];
        $days = SystemSetting::get('sync.schedule.days_of_week', ['monday', 'tuesday', 'wednesday', 'thursday', 'friday']);
        $dayNumbers = array_map(fn($day) => $dayMap[$day] ?? 1, $days);
        $cron = "0 {$hour} * * " . implode(',', $dayNumbers);
        break;
    case 'every_six_hours':
    default:
        $cron = '0 */6 * * *';
        break;
}

echo "  Frequency: {$frequency}\n";
echo "  Generated cron: {$cron}\n";

echo "\n3. ACTIVE SHOPS FOR AUTO-SYNC:\n";
$shops = \App\Models\PrestaShopShop::where('is_active', true)
    ->where('auto_sync_products', true)
    ->get(['id', 'name', 'connection_status', 'auto_sync_products']);

foreach ($shops as $shop) {
    echo "  [{$shop->id}] {$shop->name} - connection: {$shop->connection_status}, auto_sync: " . ($shop->auto_sync_products ? 'yes' : 'no') . "\n";
}

echo "\n4. RECENT SYNC_JOBS (last 24h):\n";
$jobs = DB::table('sync_jobs')
    ->where('job_type', 'import_products')
    ->where('created_at', '>', now()->subHours(24))
    ->orderBy('created_at', 'asc')
    ->get(['id', 'created_at', 'status', 'trigger_type']);

echo "  Total: " . count($jobs) . " jobs in last 24 hours\n";
$prevTime = null;
foreach ($jobs as $job) {
    $interval = $prevTime ? \Carbon\Carbon::parse($job->created_at)->diffInMinutes(\Carbon\Carbon::parse($prevTime)) : '-';
    echo "  [{$job->id}] {$job->created_at} - {$job->status} ({$job->trigger_type}) - interval: {$interval}min\n";
    $prevTime = $job->created_at;
}

echo "\n=== DONE ===\n";
