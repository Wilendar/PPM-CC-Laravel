<?php

/**
 * Test Script: getTaxRuleGroups() API Integration
 *
 * FAZA 5.1 Part 1 - Tax Rules API Integration Test
 *
 * Tests the new getTaxRuleGroups() method in PrestaShop clients
 *
 * Usage:
 *   php artisan tinker
 *   include '_TEMP/test_tax_rules_api.php';
 */

use App\Models\PrestaShopShop;
use App\Services\PrestaShop\PrestaShopClientFactory;

echo "\n=== TAX RULES API TEST ===\n\n";

// Test shop: "B2B Test DEV" (ID: 1)
$shopId = 1;

try {
    // Load shop
    $shop = PrestaShopShop::find($shopId);

    if (!$shop) {
        echo "ERROR: Shop with ID {$shopId} not found\n";
        echo "Available shops:\n";

        $shops = PrestaShopShop::select('id', 'name')->get();
        foreach ($shops as $s) {
            echo "  [{$s->id}] {$s->name}\n";
        }

        return;
    }

    echo "Shop: {$shop->name}\n";
    echo "URL: {$shop->url}\n";
    echo "Version: {$shop->version}\n\n";

    // Create API client
    $client = PrestaShopClientFactory::create($shop);

    echo "Client: " . get_class($client) . "\n";
    echo "API Version: {$client->getVersion()}\n\n";

    // Skip testConnection (goes directly to getTaxRuleGroups)
    // Fetch tax rule groups
    echo "Fetching tax rule groups...\n";

    $startTime = microtime(true);
    $taxRuleGroups = $client->getTaxRuleGroups();
    $executionTime = (microtime(true) - $startTime) * 1000; // Convert to ms

    echo "✅ Fetched in " . round($executionTime, 2) . " ms\n\n";

    // Display results
    if (empty($taxRuleGroups)) {
        echo "⚠️  No active tax rule groups found\n";
        return;
    }

    echo "Tax Rule Groups:\n";
    echo str_repeat('-', 60) . "\n";

    foreach ($taxRuleGroups as $index => $group) {
        $number = $index + 1;
        echo "[{$number}] {$group['name']}\n";
        echo "    ID: {$group['id']}\n";
        echo "    Rate: " . ($group['rate'] !== null ? $group['rate'] . '%' : 'N/A') . "\n";
        echo "    Active: " . ($group['active'] ? 'Yes' : 'No') . "\n";
        echo "\n";
    }

    echo str_repeat('-', 60) . "\n";
    echo "Total: " . count($taxRuleGroups) . " active groups\n\n";

    // Test data structure
    echo "Data Structure Validation:\n";

    $sampleGroup = reset($taxRuleGroups);

    $requiredKeys = ['id', 'name', 'rate', 'active'];
    $allKeysPresent = true;

    foreach ($requiredKeys as $key) {
        $present = array_key_exists($key, $sampleGroup);
        $status = $present ? '✅' : '❌';
        echo "  {$status} '{$key}' key " . ($present ? 'present' : 'MISSING') . "\n";

        if (!$present) {
            $allKeysPresent = false;
        }
    }

    echo "\n";

    if ($allKeysPresent) {
        echo "✅ API integration working correctly\n";
    } else {
        echo "❌ Data structure validation FAILED\n";
    }

    // Test autoDetectTaxRules compatibility
    echo "\nAutoDetectTaxRules Compatibility Test:\n";

    $polishRates = [23, 8, 5, 0];
    $foundRates = [];

    foreach ($taxRuleGroups as $group) {
        $name = strtolower($group['name']);

        if (str_contains($name, '23%') || str_contains($name, 'standard')) {
            $foundRates[23] = $group['id'];
        } elseif (str_contains($name, '8%')) {
            $foundRates[8] = $group['id'];
        } elseif (str_contains($name, '5%')) {
            $foundRates[5] = $group['id'];
        } elseif (str_contains($name, '0%') || str_contains($name, 'exempt')) {
            $foundRates[0] = $group['id'];
        }
    }

    foreach ($polishRates as $rate) {
        if (isset($foundRates[$rate])) {
            echo "  ✅ {$rate}% rate found (ID: {$foundRates[$rate]})\n";
        } else {
            echo "  ⚠️  {$rate}% rate NOT found\n";
        }
    }

    echo "\n=== TEST COMPLETED ===\n\n";

} catch (\App\Exceptions\PrestaShopAPIException $e) {
    echo "\n❌ PrestaShop API Error:\n";
    echo "   Status: {$e->getHttpStatusCode()}\n";
    echo "   Message: {$e->getMessage()}\n";

    $context = $e->getContext();
    if (isset($context['response_body'])) {
        echo "   Response: " . substr($context['response_body'], 0, 200) . "...\n";
    }

} catch (\Exception $e) {
    echo "\n❌ Error: {$e->getMessage()}\n";
    echo "   File: {$e->getFile()}:{$e->getLine()}\n";
    echo "\n   Stack trace:\n";

    $trace = $e->getTraceAsString();
    $lines = explode("\n", $trace);
    foreach (array_slice($lines, 0, 5) as $line) {
        echo "     {$line}\n";
    }
}
