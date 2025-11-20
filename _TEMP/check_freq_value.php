<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$setting = \App\Models\SystemSetting::where('key', 'sync.schedule.frequency')->first();
echo $setting ? $setting->value : 'NOT FOUND';
