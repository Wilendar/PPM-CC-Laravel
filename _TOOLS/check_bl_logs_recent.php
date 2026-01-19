<?php
require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

$logs = DB::table('integration_logs')
    ->where('integration_type', 'baselinker')
    ->orderBy('logged_at', 'desc')
    ->limit(10)
    ->get();

echo "=== LAST 10 BASELINKER LOGS ===\n\n";

foreach ($logs as $log) {
    echo "ID: {$log->id} | {$log->operation} | {$log->logged_at}\n";
    echo "Description: {$log->description}\n";

    // Show images if present in request
    $reqData = json_decode($log->request_data ?? '{}', true);
    if (isset($reqData['parameters']['images'])) {
        echo "IMAGES: " . count($reqData['parameters']['images']) . " images\n";
        foreach ($reqData['parameters']['images'] as $i => $url) {
            echo "  [$i] $url\n";
        }
    }

    // Show response status
    $resData = json_decode($log->response_data ?? '{}', true);
    echo "Status: " . ($resData['status'] ?? 'N/A') . "\n";

    echo "---\n\n";
}
