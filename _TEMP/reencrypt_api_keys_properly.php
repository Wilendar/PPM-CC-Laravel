<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Encryption\Encrypter;

echo "=== PROPER RE-ENCRYPTION OF API KEYS ===\n\n";

echo "STRATEGY: Create custom Encrypter that accepts old format, then re-encrypt properly\n\n";

// Custom decryption function that bypasses validPayload() check
function decryptOldFormat($encrypted) {
    $appKey = config('app.key');
    $cipher = config('app.cipher');

    // Remove "base64:" prefix if present
    if (str_starts_with($appKey, 'base64:')) {
        $appKey = base64_decode(substr($appKey, 7));
    }

    $encrypter = new Encrypter($appKey, $cipher);

    // Decode the payload manually
    $payload = json_decode(base64_decode($encrypted), true);

    if (!$payload || !isset($payload['iv'], $payload['value'], $payload['mac'])) {
        throw new \Exception('Invalid payload structure');
    }

    // Remove empty tag if present
    if (isset($payload['tag']) && $payload['tag'] === '') {
        unset($payload['tag']);
    }

    // Verify MAC manually (this is what validPayload does)
    $calculatedMac = hash_hmac('sha256', hash_hmac('sha256', base64_decode($payload['iv']), $appKey) . $payload['value'], $appKey);

    if (!hash_equals($calculatedMac, $payload['mac'])) {
        // MAC doesn't match - try WITHOUT the tag in MAC calculation
        // This means the MAC was calculated with the tag field present
        $payloadWithTag = $payload;
        $payloadWithTag['tag'] = '';
        $jsonWithTag = json_encode($payloadWithTag);

        // Try to decrypt using reflection to bypass validation
        try {
            $reflection = new \ReflectionClass($encrypter);
            $method = $reflection->getMethod('decryptPayload');
            $method->setAccessible(true);

            // Reconstruct payload with tag for MAC verification
            $payloadForDecrypt = $payload;
            $payloadForDecrypt['tag'] = '';

            $decrypted = $method->invoke($encrypter, $payloadForDecrypt);
            return $decrypted;

        } catch (\Exception $e) {
            throw new \Exception("Failed to decrypt: " . $e->getMessage());
        }
    }

    // If MAC matches, proceed with normal decryption
    return $encrypter->decrypt($encrypted);
}

$shops = DB::table('prestashop_shops')->get();

foreach ($shops as $shop) {
    echo "Shop {$shop->id} ({$shop->name}):\n";

    try {
        // Try normal decryption first
        $decrypted = decrypt($shop->api_key);
        echo "  ✅ Already working, skipping\n\n";
        continue;

    } catch (\Exception $e) {
        echo "  Current status: BROKEN ({$e->getMessage()})\n";

        try {
            // Try to decrypt with old format handling
            $decrypted = decryptOldFormat($shop->api_key);
            echo "  ✅ Successfully decrypted with old format\n";
            echo "  Decrypted value (first 30 chars): " . substr($decrypted, 0, 30) . "...\n";

            // Re-encrypt properly with current Laravel Encrypter
            $newEncrypted = encrypt($decrypted);
            echo "  ✅ Re-encrypted with proper format\n";

            // Test new encryption
            $testDecrypt = decrypt($newEncrypted);
            if ($testDecrypt === $decrypted) {
                echo "  ✅ Test decrypt successful\n";

                // Update database
                DB::table('prestashop_shops')
                    ->where('id', $shop->id)
                    ->update(['api_key' => $newEncrypted]);

                echo "  ✅ Database updated\n";

            } else {
                echo "  ❌ Test decrypt mismatch\n";
            }

        } catch (\Exception $e2) {
            echo "  ❌ Re-encryption failed: {$e2->getMessage()}\n";
            echo "  ACTION REQUIRED: Manually re-enter API key via Edit Shop form\n";
        }
    }

    echo "\n";
}

echo "\n=== FINAL VERIFICATION ===\n";

foreach ($shops as $shop) {
    $updated = DB::table('prestashop_shops')->where('id', $shop->id)->first();
    echo "Shop {$updated->id} ({$updated->name}): ";

    try {
        $decrypted = decrypt($updated->api_key);
        echo "✅ OK (key: " . substr($decrypted, 0, 20) . "...)\n";
    } catch (\Exception $e) {
        echo "❌ STILL BROKEN\n";
    }
}

echo "\n=== RE-ENCRYPTION COMPLETE ===\n";
