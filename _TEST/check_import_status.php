<?php
/**
 * Check import status for product 323429561
 */

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\SyncJob;
use App\Models\JobProgress;
use App\Models\ERPConnection;
use Illuminate\Support\Facades\Http;

echo "=== Recent SyncJobs ===\n";
$jobs = SyncJob::orderBy('id', 'desc')->take(5)->get();
foreach ($jobs as $job) {
    echo "ID: {$job->id} | Status: {$job->status} | Name: {$job->job_name}\n";
    if ($job->filters) {
        echo "  Filters: " . json_encode($job->filters) . "\n";
    }
    if ($job->error_log) {
        echo "  Error: " . json_encode($job->error_log) . "\n";
    }
    echo "\n";
}

echo "=== Recent JobProgress ===\n";
$progresses = JobProgress::orderBy('id', 'desc')->take(3)->get();
foreach ($progresses as $progress) {
    echo "ID: {$progress->id} | Status: {$progress->status} | {$progress->current_count}/{$progress->total_count}\n";
    if ($progress->error_details) {
        echo "  Errors: " . json_encode($progress->error_details) . "\n";
    }
}

echo "\n=== Check Baselinker API for Product 323429561 ===\n";
$connection = ERPConnection::find(1);
if ($connection) {
    $config = $connection->connection_config;

    $response = Http::timeout(30)->asForm()->post('https://api.baselinker.com/connector.php', [
        'token' => $config['api_token'],
        'method' => 'getInventoryProductsData',
        'parameters' => json_encode([
            'inventory_id' => (int)$config['inventory_id'],
            'products' => [323429561],
        ])
    ]);

    $data = $response->json();
    echo "API Status: " . ($data['status'] ?? 'N/A') . "\n";

    if (isset($data['products'][323429561])) {
        $product = $data['products'][323429561];
        echo "Product found!\n";
        echo "  Name: " . ($product['text_fields']['name'] ?? 'N/A') . "\n";
        echo "  SKU: " . ($product['sku'] ?? 'EMPTY') . "\n";
        echo "  Is bundle: " . (isset($product['is_bundle']) ? ($product['is_bundle'] ? 'YES' : 'NO') : 'N/A') . "\n";
        echo "  Variants key exists: " . (isset($product['variants']) ? 'YES' : 'NO') . "\n";
        if (isset($product['variants'])) {
            echo "  Variants count: " . count($product['variants']) . "\n";
            echo "  Variants structure:\n";
            print_r($product['variants']);
        }
        echo "  Images count: " . count($product['images'] ?? []) . "\n";
    } else {
        echo "Product NOT found in API response!\n";
        echo "Response keys: " . implode(', ', array_keys($data['products'] ?? [])) . "\n";
    }
}

echo "\n=== Check Laravel Logs (last 30 lines with errors) ===\n";
$logFile = storage_path('logs/laravel.log');
if (file_exists($logFile)) {
    $lines = file($logFile);
    $lastLines = array_slice($lines, -50);
    foreach ($lastLines as $line) {
        if (stripos($line, 'error') !== false || stripos($line, 'exception') !== false || stripos($line, '323429561') !== false) {
            echo $line;
        }
    }
}
