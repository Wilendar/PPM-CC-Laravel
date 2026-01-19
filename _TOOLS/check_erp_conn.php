<?php
/**
 * Check ERP Connection Config and Job Status
 */
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== ERP CONNECTION ===\n";
// Use model to properly decrypt config
$conn = App\Models\ERPConnection::find(1);
echo "Type: " . $conn->erp_type . "\n";
echo "Instance: " . $conn->instance_name . "\n";
echo "Connection Status: " . ($conn->connection_status ?? 'N/A') . "\n";

$config = $conn->connection_config; // This should be auto-decrypted via $casts
echo "Config (decrypted via model):\n";
if ($config && is_array($config)) {
    echo "  - API Token: " . (isset($config['api_token']) ? substr($config['api_token'], 0, 10) . '...' : 'NOT SET') . "\n";
    echo "  - Inventory ID: " . ($config['inventory_id'] ?? 'NOT SET!!') . "\n";
    echo "  - All keys: " . implode(', ', array_keys($config)) . "\n";
} else {
    echo "  CONFIG IS NULL OR INVALID! Type: " . gettype($config) . "\n";
}

echo "\n=== JOBS IN QUEUE ===\n";
$jobs = DB::table('jobs')->get();
echo "Total jobs: " . $jobs->count() . "\n";
foreach ($jobs as $job) {
    $payload = json_decode($job->payload, true);
    echo "Job ID: " . $job->id . " Queue: " . $job->queue . " Class: " . ($payload['displayName'] ?? 'unknown') . "\n";
    echo "  Created: " . date('Y-m-d H:i:s', $job->created_at) . "\n";
    echo "  Available at: " . date('Y-m-d H:i:s', $job->available_at) . "\n";
    echo "  Attempts: " . $job->attempts . "\n";

    // Check if job is stuck (created more than 5 minutes ago)
    if (time() - $job->created_at > 300) {
        echo "  *** JOB IS STUCK! ***\n";
    }
}

echo "\n=== RECENT INTEGRATION LOGS (last 10) ===\n";
$logs = DB::table('integration_logs')
    ->where('integration_type', 'baselinker')
    ->orderBy('logged_at', 'desc')
    ->limit(10)
    ->get();

foreach ($logs as $log) {
    echo "\n[" . $log->logged_at . "] " . $log->log_level . " | " . $log->operation . "\n";
    echo "  Description: " . $log->description . "\n";
    if ($log->error_message) {
        echo "  ERROR: " . $log->error_message . "\n";
    }
    if ($log->http_status) {
        echo "  HTTP Status: " . $log->http_status . "\n";
    }
    if ($log->request_data) {
        echo "  Request: " . substr($log->request_data, 0, 200) . (strlen($log->request_data) > 200 ? '...' : '') . "\n";
    }
    if ($log->response_data) {
        echo "  Response: " . substr($log->response_data, 0, 200) . (strlen($log->response_data) > 200 ? '...' : '') . "\n";
    }
}
