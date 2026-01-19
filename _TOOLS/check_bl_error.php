<?php
require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

$logId = $argv[1] ?? 283;

$log = DB::table('integration_logs')->where('id', $logId)->first();

if (!$log) {
    echo "Log not found: $logId\n";
    exit(1);
}

echo "=== LOG ID: {$log->id} ===\n";
echo "Operation: {$log->operation}\n";
echo "Description: {$log->description}\n";
echo "Logged at: {$log->logged_at}\n\n";

echo "=== REQUEST DATA ===\n";
$reqData = json_decode($log->request_data ?? '{}', true);
print_r($reqData);

echo "\n=== RESPONSE DATA ===\n";
$resData = json_decode($log->response_data ?? '{}', true);
print_r($resData);

echo "\n=== ERROR MESSAGE ===\n";
echo $log->error_message ?? 'N/A';
echo "\n";
