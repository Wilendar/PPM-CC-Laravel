<?php
require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== OSTATNIE 15 LOGOW BASELINKER ===\n\n";

$logs = DB::table('integration_logs')
    ->where('integration_type', 'baselinker')
    ->orderByDesc('id')
    ->limit(15)
    ->get(['id', 'operation', 'description', 'request_data', 'response_data', 'logged_at']);

foreach ($logs as $log) {
    $reqData = json_decode($log->request_data ?? '{}', true);
    $resData = json_decode($log->response_data ?? '{}', true);

    $name = $reqData['parameters']['text_fields']['name'] ?? ($reqData['name'] ?? 'N/A');
    $status = $resData['status'] ?? 'N/A';

    echo "ID: {$log->id}\n";
    echo "  Operation: {$log->operation}\n";
    echo "  Description: " . substr($log->description ?? '', 0, 60) . "\n";
    echo "  Name sent: " . substr($name, 0, 40) . "\n";
    echo "  Status: {$status}\n";
    echo "  Time: {$log->logged_at}\n";
    echo "---\n";
}
