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

// ==========================================
// DATABASE CLEANUP TASKS (CRITICAL!)
// ==========================================
// 2025-01-19: FIX - Te tabele rosly do gigabajtow bez regularnego czyszczenia!

// Telescope entries cleanup - keep only 48 hours of data
// CRITICAL: Bez tego telescope_entries rosnie do dziesiatek GB!
Schedule::command('telescope:prune --hours=48')
    ->daily()
    ->at('03:00')
    ->name('telescope-prune')
    ->withoutOverlapping()
    ->runInBackground();

// Price history cleanup - keep 90 days of audit trail
// CRITICAL: Bez tego price_history rosnie do dziesiatek GB!
// JSON columns (old_values, new_values) moga miec setki KB per rekord
Schedule::command('price-history:cleanup --days=90 --chunk=5000')
    ->daily()
    ->at('03:30')
    ->name('price-history-cleanup')
    ->withoutOverlapping()
    ->runInBackground();

// Log tables cleanup - sync_logs, integration_logs, failed_jobs, notifications
// Uses retention policies from config/database-cleanup.php
Schedule::command('logs:cleanup')
    ->daily()
    ->at('04:00')
    ->name('logs-tables-cleanup')
    ->withoutOverlapping()
    ->runInBackground();

// Database health check with email alerts
// Monitors all table sizes, sends alerts when thresholds exceeded
Schedule::command('db:health-check --alert')
    ->daily()
    ->at('06:00')
    ->name('db-health-check')
    ->withoutOverlapping()
    ->runInBackground();

// ==========================================
// QUEUE WORKER (SHARED HOSTING)
// ==========================================
// 2026-01-19: Auto-process queue jobs every minute (shared hosting compatible)
// On shared hosting we can't run `queue:work` as daemon, so we use scheduler

Schedule::command('queue:work database --queue=erp_default,erp_high,default,sync --once --max-time=55')
    ->everyMinute()
    ->name('queue-worker-erp')
    ->withoutOverlapping()
    ->runInBackground();

// ==========================================
// PRESTASHOP SYNC TASKS
// ==========================================

// FIX #3 - BUG #7: Import products from PrestaShop (2025-11-12)
// ETAP_07 FAZA 9.2: Dynamic scheduler frequency from SystemSettings (2025-11-13)
// Pull current product data from PrestaShop → PPM with configurable schedule
use App\Jobs\PullProductsFromPrestaShop;
use App\Models\PrestaShopShop;
use App\Models\SyncJob;
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
            // JOB DEDUPLICATION: Skip if pending/running SyncJob already exists for this shop
            // This is an additional layer of protection beyond ShouldBeUnique
            $existingPending = SyncJob::where('source_id', $shop->id)
                ->where('source_type', SyncJob::TYPE_PRESTASHOP)
                ->where('job_type', 'import_products')
                ->whereIn('status', [SyncJob::STATUS_PENDING, SyncJob::STATUS_RUNNING])
                ->exists();

            if ($existingPending) {
                \Log::info('PullProductsFromPrestaShop skipped - pending/running job exists', [
                    'shop_id' => $shop->id,
                    'shop_name' => $shop->name,
                ]);
                continue;
            }

            PullProductsFromPrestaShop::dispatch($shop);
        }
    } catch (\Exception $e) {
        // Fail silently if system_settings table doesn't exist yet
        // This allows migrations to run without breaking scheduler
    }
})->name('prestashop:pull-products-scheduled')
  ->cron($buildSyncCronExpression())
  ->withoutOverlapping(); // Note: Closures cannot use runInBackground()

// ==========================================
// SUBIEKT GT ERP SYNC TASKS
// ==========================================
// ETAP: Subiekt GT Integration - Scheduled pull operations

use App\Jobs\ERP\PullProductsFromSubiektGT;
use App\Jobs\ERP\DetectSubiektGTChanges;
use App\Models\ERPConnection;

// Subiekt GT Change Detection (lightweight check every 15 minutes)
// Dispatches incremental pull if changes detected
Schedule::call(function () {
    try {
        // Get all active Subiekt GT connections with auto-sync enabled
        $subiektConnections = ERPConnection::where('erp_type', ERPConnection::ERP_SUBIEKT_GT)
            ->where('is_active', true)
            ->where('auto_sync_products', true)
            ->get();

        foreach ($subiektConnections as $connection) {
            DetectSubiektGTChanges::dispatch($connection->id);
        }
    } catch (\Exception $e) {
        // Fail silently if erp_connections table doesn't exist yet
        \Log::warning('Subiekt GT change detection scheduler failed: ' . $e->getMessage());
    }
})->name('subiekt-gt:change-detection')
  ->everyFifteenMinutes()
  ->withoutOverlapping();

// Subiekt GT Full Sync (every 6 hours)
// Full product pull for active Subiekt GT connections
Schedule::call(function () {
    try {
        $subiektConnections = ERPConnection::where('erp_type', ERPConnection::ERP_SUBIEKT_GT)
            ->where('is_active', true)
            ->where('auto_sync_products', true)
            ->get();

        foreach ($subiektConnections as $connection) {
            // Skip if already has pending/running sync job
            $existingJob = SyncJob::where('target_type', 'subiekt_gt')
                ->where('target_id', $connection->id)
                ->where('job_type', 'pull_products')
                ->whereIn('status', ['pending', 'processing'])
                ->exists();

            if ($existingJob) {
                \Log::info('Subiekt GT full sync skipped - job already pending', [
                    'connection_id' => $connection->id,
                ]);
                continue;
            }

            // Create SyncJob for tracking
            $syncJob = SyncJob::create([
                'target_type' => 'subiekt_gt',
                'target_id' => $connection->id,
                'job_type' => 'pull_products',
                'status' => 'pending',
                'status_message' => 'Scheduled full sync',
                'metadata' => [
                    'mode' => 'full',
                    'triggered_by' => 'scheduler',
                ],
            ]);

            PullProductsFromSubiektGT::dispatch(
                $connection->id,
                'full',
                null,
                5000,
                100,
                $syncJob->id
            );
        }
    } catch (\Exception $e) {
        \Log::warning('Subiekt GT full sync scheduler failed: ' . $e->getMessage());
    }
})->name('subiekt-gt:full-sync')
  ->everySixHours()
  ->withoutOverlapping();

// ==========================================
// FUTURE TASKS (PLACEHOLDER)
// ==========================================

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