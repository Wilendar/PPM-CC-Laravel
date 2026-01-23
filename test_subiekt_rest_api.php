<?php
/**
 * Test script for Subiekt GT REST API Integration
 *
 * Run: php test_subiekt_rest_api.php
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\ERP\SubiektGT\SubiektRestApiClient;
use App\Services\ERP\SubiektGTService;
use App\Models\ERPConnection;

echo "=== Subiekt GT REST API Integration Test ===\n\n";

// API Configuration
$config = [
    'rest_api_url' => 'https://sapi.mpptrade.pl',  // Subiekt API via IIS (EXEA)
    'rest_api_key' => 'YHZ4AtJiNBrEFhez7AvPTGJK3XKCrX4NCyGLwrQpecqCyvP3XxxCGYRvjdmtGkRb',
    'rest_api_timeout' => 30,
    'rest_api_connect_timeout' => 10,
    'rest_api_verify_ssl' => false,
    'connection_mode' => 'rest_api',
];

try {
    // TEST 1: Direct REST API Client
    echo "1. Testing SubiektRestApiClient directly...\n";

    $client = new SubiektRestApiClient([
        'base_url' => $config['rest_api_url'],
        'api_key' => $config['rest_api_key'],
        'timeout' => $config['rest_api_timeout'],
        'connect_timeout' => $config['rest_api_connect_timeout'],
        'verify_ssl' => $config['rest_api_verify_ssl'],
    ]);

    // Health check
    echo "   - Health check... ";
    $health = $client->healthCheck();
    if ($health['success'] ?? false) {
        echo "OK\n";
        echo "     Database: " . ($health['database'] ?? 'unknown') . "\n";
        echo "     Products: " . ($health['products_count'] ?? 0) . "\n";
        echo "     Response time: " . ($health['response_time_ms'] ?? '?') . "ms\n";
    } else {
        echo "FAILED: " . ($health['error'] ?? 'unknown error') . "\n";
    }

    // Test connection
    echo "   - testConnection()... ";
    $testResult = $client->testConnection();
    echo ($testResult['success'] ? "OK" : "FAILED") . "\n";
    if (!$testResult['success']) {
        echo "     Error: " . ($testResult['message'] ?? 'unknown') . "\n";
    }

    // Get products
    echo "   - getProducts(page=1, pageSize=5)... ";
    $products = $client->getProducts(['page' => 1, 'pageSize' => 5]);
    if ($products['success'] ?? false) {
        $productCount = count($products['data'] ?? []);
        $total = $products['pagination']['total_items'] ?? 0;
        echo "OK ({$productCount} products, {$total} total)\n";

        // Show first product
        if (!empty($products['data'])) {
            $first = $products['data'][0];
            echo "     First product:\n";
            echo "       SKU: " . ($first['sku'] ?? '-') . "\n";
            echo "       Name: " . ($first['name'] ?? '-') . "\n";
            echo "       Price Net: " . ($first['priceNet'] ?? $first['price_net'] ?? '-') . "\n";
            echo "       Stock: " . ($first['stock'] ?? '-') . "\n";
        }
    } else {
        echo "FAILED: " . ($products['error'] ?? 'unknown error') . "\n";
    }

    // Get warehouses
    echo "   - getWarehouses()... ";
    $warehouses = $client->getWarehouses(false);
    if ($warehouses['success'] ?? false) {
        $whCount = count($warehouses['data'] ?? []);
        echo "OK ({$whCount} warehouses)\n";
    } else {
        echo "FAILED\n";
    }

    // Get price levels
    echo "   - getPriceLevels()... ";
    $priceLevels = $client->getPriceLevels(false);
    if ($priceLevels['success'] ?? false) {
        $plCount = count($priceLevels['data'] ?? []);
        echo "OK ({$plCount} price levels)\n";
    } else {
        echo "FAILED\n";
    }

    // Get VAT rates
    echo "   - getVatRates()... ";
    $vatRates = $client->getVatRates(false);
    if ($vatRates['success'] ?? false) {
        $vrCount = count($vatRates['data'] ?? []);
        echo "OK ({$vrCount} VAT rates)\n";
    } else {
        echo "FAILED\n";
    }

    echo "\n";

    // TEST 2: SubiektGTService
    echo "2. Testing SubiektGTService...\n";

    $service = new SubiektGTService();

    // Test connection via service
    echo "   - testConnection()... ";
    $serviceTestResult = $service->testConnection($config);
    echo ($serviceTestResult['success'] ? "OK" : "FAILED") . "\n";
    if ($serviceTestResult['success']) {
        echo "     Response time: " . ($serviceTestResult['response_time'] ?? '?') . "ms\n";
        echo "     Connection mode: " . ($serviceTestResult['details']['connection_mode'] ?? 'unknown') . "\n";
    } else {
        echo "     Error: " . ($serviceTestResult['message'] ?? 'unknown') . "\n";
    }

    // Test authentication
    echo "   - testAuthentication()... ";
    $authResult = $service->testAuthentication($config);
    echo ($authResult['success'] ? "OK" : "FAILED") . "\n";
    if ($authResult['success']) {
        $details = $authResult['details'] ?? [];
        echo "     Warehouses: " . count($details['warehouses'] ?? []) . "\n";
        echo "     Price types: " . count($details['price_types'] ?? []) . "\n";
        $stats = $details['database_stats'] ?? [];
        echo "     Products in DB: " . ($stats['product_count'] ?? 0) . "\n";
    }

    echo "\n";
    echo "=== All tests completed ===\n";

} catch (\Exception $e) {
    echo "\n\nFATAL ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
}
