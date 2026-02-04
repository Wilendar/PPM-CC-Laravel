<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "dev_auth_bypass: ";
var_dump(App\Models\SystemSetting::get('dev_auth_bypass', false));

echo "\nDEV_AUTH_BYPASS env: ";
var_dump(env('DEV_AUTH_BYPASS', 'NOT_SET'));
