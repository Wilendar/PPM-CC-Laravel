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