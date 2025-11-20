<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== RE-ENCRYPT API KEYS FROM PLAINTEXT ===\n\n";

// Known plaintext API keys from dane_hostingu.md
$knownKeys = [
    1 => 'RPV43WNRX8Y7ZJWAPXU3ZA1Z9ZEE9Y22',  // Shop 1: dev.mpptrade.pl
    5 => '1ZEUFUI8JTYY5Z9XXQV2RRANZTKK4R77',  // Shop 5: test.kayomoto.pl
];

foreach ($knownKeys as $shopId => $plaintextKey) {
    $shop = DB::table('prestashop_shops')->where('id', $shopId)->first();

    if (!$shop) {
        echo "❌ Shop {$shopId} NOT FOUND\n";
        continue;
    }

    echo "Shop {$shopId} ({$shop->name}):\n";
    echo "  Plaintext key: " . substr($plaintextKey, 0, 20) . "..." . substr($plaintextKey, -10) . "\n";

    // Encrypt properly with Laravel's encrypt()
    $encrypted = encrypt($plaintextKey);
    echo "  ✅ Encrypted with current Laravel Encrypter\n";

    // Test decryption
    try {
        $testDecrypt = decrypt($encrypted);

        if ($testDecrypt === $plaintextKey) {
            echo "  ✅ Test decrypt successful - match!\n";

            // Update database
            DB::table('prestashop_shops')
                ->where('id', $shopId)
                ->update(['api_key' => $encrypted]);

            echo "  ✅ Database updated\n";

        } else {
            echo "  ❌ Test decrypt mismatch\n";
            echo "     Expected: {$plaintextKey}\n";
            echo "     Got: {$testDecrypt}\n";
        }

    } catch (\Exception $e) {
        echo "  ❌ Test decrypt failed: {$e->getMessage()}\n";
    }

    echo "\n";
}

echo "\n=== FINAL VERIFICATION ===\n";

$shops = DB::table('prestashop_shops')->get();

foreach ($shops as $shop) {
    echo "Shop {$shop->id} ({$shop->name}): ";

    try {
        $decrypted = decrypt($shop->api_key);
        echo "✅ OK (key: " . substr($decrypted, 0, 20) . "...)\n";
    } catch (\Exception $e) {
        echo "❌ BROKEN - {$e->getMessage()}\n";

        if ($shop->id == 6) {
            echo "   NOTE: Shop 6 (TEST YCF) API key not found in dane_hostingu.md\n";
            echo "   ACTION: Manually re-enter API key via Edit Shop form\n";
        }
    }
}

echo "\n=== RE-ENCRYPTION COMPLETE ===\n";
