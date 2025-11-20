<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Console Routes
|--------------------------------------------------------------------------
|
| PPM-CC-Laravel Console Commands & Scheduled Tasks
| FAZA A: Spatie Setup + Middleware - Console foundation
|
*/

// ==========================================
// ARTISAN COMMANDS
// ==========================================

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

// PPM-specific commands będą dodane w kolejnych fazach:
// php artisan ppm:sync-prestashop {shop_id}
// php artisan ppm:import-xlsx {file_path}  
// php artisan ppm:sync-erp {erp_system}

// ==========================================
// SCHEDULED TASKS
// ==========================================

// ETAP_07 FAZA 3D: Category Preview & Job Progress Cleanup
// Automatic cleanup dla expired category preview records
Schedule::command('category-preview:cleanup')
    ->hourly()
    ->withoutOverlapping()
    ->runInBackground();

// Automatic cleanup dla stuck job progress (pending >30min)
Schedule::command('jobs:cleanup-stuck --minutes=30')
    ->hourly()
    ->withoutOverlapping()
    ->runInBackground();

// Automatic log archival - move old logs to archive/ folder
// 2025-10-14: Changed time to 00:01 for daily rotation at midnight
Schedule::command('logs:archive --keep-days=30')
    ->daily()
    ->at('00:01')
    ->withoutOverlapping()
    ->runInBackground();

// Automatic sync_jobs cleanup - retention policy from config/sync.php
// 2025-11-12: BUG #9 FIX #6 - Configurable retention policy + optional auto-cleanup
if (config('sync.cleanup.auto_cleanup_enabled', false)) {
    Schedule::command('sync:cleanup')
        ->daily()
        ->at('02:00')
        ->name('sync-jobs-cleanup')
        ->withoutOverlapping()
        ->runInBackground();
}

// FIX #3 - BUG #7: Import products from PrestaShop (2025-11-12)
// ETAP_07 FAZA 9.2: Dynamic scheduler frequency from SystemSettings (2025-11-13)
// Pull current product data from PrestaShop → PPM with configurable schedule
use App\Jobs\PullProductsFromPrestaShop;
use App\Models\PrestaShopShop;
use App\Models\SystemSetting;

// Build dynamic cron expression from settings (with safe fallback)
$buildSyncCronExpression = function (): string {
    try {
        $frequency = SystemSetting::get('sync.schedule.frequency', 'every_six_hours');
        $hour = SystemSetting::get('sync.schedule.hour', 2);
        $days = SystemSetting::get('sync.schedule.days_of_week', ['monday', 'tuesday', 'wednesday', 'thursday', 'friday']);

        switch ($frequency) {
            case 'hourly':
                return '0 * * * *';
            case 'daily':
                return "0 {$hour} * * *";
            case 'weekly':
                $dayMap = ['sunday' => 0, 'monday' => 1, 'tuesday' => 2, 'wednesday' => 3, 'thursday' => 4, 'friday' => 5, 'saturday' => 6];
                $dayNumbers = array_map(fn($day) => $dayMap[$day] ?? 1, $days);
                return "0 {$hour} * * " . implode(',', $dayNumbers);
            case 'every_six_hours':
            default:
                return '0 */6 * * *'; // Fallback to 6 hours
        }
    } catch (\Exception $e) {
        // Fallback if system_settings table doesn't exist yet
        return '0 */6 * * *';
    }
};

Schedule::call(function () {
    try {
        // Check if auto-sync is enabled globally
        if (!SystemSetting::get('sync.schedule.enabled', true)) {
            return;
        }

        // Check maintenance mode preference
        if (SystemSetting::get('sync.schedule.skip_maintenance', true) && app()->isDownForMaintenance()) {
            return;
        }

        $query = PrestaShopShop::where('is_active', true)
            ->where('auto_sync_products', true);

        // Apply "only connected" filter
        if (SystemSetting::get('sync.schedule.only_connected', true)) {
            $query->where('connection_status', 'connected');
        }

        $activeShops = $query->get();

        foreach ($activeShops as $shop) {
            PullProductsFromPrestaShop::dispatch($shop);
        }
    } catch (\Exception $e) {
        // Fail silently if system_settings table doesn't exist yet
        // This allows migrations to run without breaking scheduler
    }
})->name('prestashop:pull-products-scheduled')
  ->cron($buildSyncCronExpression())
  ->withoutOverlapping(); // Note: Closures cannot use runInBackground()

// Schedule będzie skonfigurowany w kolejnych fazach
// na razie placeholder dla przyszłych zadań

/*
Schedule::command('ppm:sync-prestashop --all')
    ->hourly()
    ->withoutOverlapping()
    ->onOneServer();

Schedule::command('ppm:sync-erp baselinker')
    ->everyFifteenMinutes()
    ->withoutOverlapping();

Schedule::command('ppm:cleanup-temp-files')
    ->daily()
    ->at('02:00');

Schedule::command('permission:cache-reset')
    ->weekly()
    ->sundays()
    ->at('03:00');
*/