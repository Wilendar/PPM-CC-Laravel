<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== FIX ENCRYPTED API KEYS ===\n\n";

echo "STRATEGY: Remove empty 'tag' key from encrypted values\n\n";

$shops = DB::table('prestashop_shops')->get();

foreach ($shops as $shop) {
    echo "Shop {$shop->id} ({$shop->name}):\n";

    $encrypted = $shop->api_key;

    // Decode
    $decoded = base64_decode($encrypted, true);
    if ($decoded === false) {
        echo "  ❌ NOT valid base64, skipping\n\n";
        continue;
    }

    $json = json_decode($decoded, true);
    if ($json === null) {
        echo "  ❌ NOT valid JSON, skipping\n\n";
        continue;
    }

    // Check if tag is empty
    if (isset($json['tag']) && $json['tag'] === '') {
        echo "  Found empty tag, removing...\n";

        // Remove tag key
        unset($json['tag']);

        // Re-encode
        $newEncoded = json_encode($json);
        $newEncrypted = base64_encode($newEncoded);

        echo "  Old length: " . strlen($encrypted) . "\n";
        echo "  New length: " . strlen($newEncrypted) . "\n";

        // Test decryption BEFORE saving
        try {
            $testDecrypt = decrypt($newEncrypted);
            echo "  ✅ TEST DECRYPT SUCCESSFUL!\n";
            echo "  Decrypted value (first 30 chars): " . substr($testDecrypt, 0, 30) . "...\n";

            // Update database
            DB::table('prestashop_shops')
                ->where('id', $shop->id)
                ->update(['api_key' => $newEncrypted]);

            echo "  ✅ Database updated\n";

        } catch (\Exception $e) {
            echo "  ❌ TEST DECRYPT FAILED: {$e->getMessage()}\n";
            echo "  NOT updating database (keeping old value)\n";
        }

    } else {
        echo "  No empty tag found\n";
    }

    echo "\n";
}

echo "\n=== VERIFICATION ===\n";

foreach ($shops as $shop) {
    $updated = DB::table('prestashop_shops')->where('id', $shop->id)->first();
    echo "Shop {$updated->id} ({$updated->name}): ";

    try {
        decrypt($updated->api_key);
        echo "✅ OK\n";
    } catch (\Exception $e) {
        echo "❌ STILL BROKEN: {$e->getMessage()}\n";
    }
}

echo "\n=== FIX COMPLETE ===\n";
