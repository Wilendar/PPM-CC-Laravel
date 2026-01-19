<?php
/**
 * Check BL status in response_data
 */
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== CHECK BL STATUS IN LOGS ===\n\n";

$log = DB::table('integration_logs')
    ->where('operation', 'LIKE', 'api_call_%')
    ->orderBy('logged_at', 'desc')
    ->first();

if (!$log) {
    die("No api_call logs found!\n");
}

echo "Log ID: {$log->id}\n";
echo "Operation: {$log->operation}\n";
echo "Logged at: {$log->logged_at}\n\n";

echo "=== RESPONSE DATA RAW ===\n";
echo $log->response_data . "\n\n";

echo "=== DECODED ===\n";
$decoded = json_decode($log->response_data, true);
if ($decoded === null) {
    echo "JSON DECODE ERROR: " . json_last_error_msg() . "\n";
} else {
    echo "status: " . ($decoded['status'] ?? 'NOT FOUND') . "\n";
    echo "product_id: " . ($decoded['product_id'] ?? 'NOT FOUND') . "\n";
    print_r($decoded);
}
