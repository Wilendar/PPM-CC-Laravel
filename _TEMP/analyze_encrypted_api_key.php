<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== ENCRYPTED API KEY ANALYSIS ===\n\n";

$shop = DB::table('prestashop_shops')->where('id', 1)->first();

if (!$shop) {
    echo "ERROR: Shop 1 NOT FOUND\n";
    exit(1);
}

echo "Shop ID: {$shop->id}\n";
echo "Shop Name: {$shop->name}\n\n";

$encryptedKey = $shop->api_key;

echo "ENCRYPTED VALUE ANALYSIS:\n";
echo "Length: " . strlen($encryptedKey) . "\n";
echo "First 100 chars: " . substr($encryptedKey, 0, 100) . "\n";
echo "Last 50 chars: " . substr($encryptedKey, -50) . "\n\n";

// Check if it's base64 encoded JSON (Laravel's encryption format)
$decoded = base64_decode($encryptedKey, true);
if ($decoded !== false) {
    echo "✅ Valid base64\n";

    $json = json_decode($decoded, true);
    if ($json !== null) {
        echo "✅ Valid JSON after base64 decode\n";
        echo "Keys present: " . implode(', ', array_keys($json)) . "\n";

        if (isset($json['iv'])) {
            echo "  IV present: " . substr($json['iv'], 0, 20) . "...\n";
        }
        if (isset($json['value'])) {
            echo "  Value present: " . substr($json['value'], 0, 20) . "...\n";
        }
        if (isset($json['mac'])) {
            echo "  MAC present: " . substr($json['mac'], 0, 20) . "...\n";
        }
        if (isset($json['tag'])) {
            echo "  Tag present: " . substr($json['tag'], 0, 20) . "...\n";
        }
    } else {
        echo "❌ NOT valid JSON after base64 decode\n";
        echo "Decoded (first 200 chars): " . substr($decoded, 0, 200) . "\n";
    }
} else {
    echo "❌ NOT valid base64\n";

    // Maybe it's plain JSON?
    $json = json_decode($encryptedKey, true);
    if ($json !== null) {
        echo "✅ It's plain JSON (not base64 encoded)\n";
        echo "Keys present: " . implode(', ', array_keys($json)) . "\n";
    } else {
        echo "❌ NOT plain JSON either\n";
    }
}

// Check APP_KEY cipher
echo "\n\nAPP_KEY CONFIG:\n";
$appKey = config('app.key');
echo "APP_KEY present: " . ($appKey ? 'YES' : 'NO') . "\n";
if ($appKey) {
    echo "APP_KEY length: " . strlen($appKey) . "\n";
    echo "APP_KEY prefix: " . substr($appKey, 0, 15) . "...\n";
}

$cipher = config('app.cipher');
echo "Cipher: {$cipher}\n";

echo "\n=== ANALYSIS COMPLETE ===\n";
