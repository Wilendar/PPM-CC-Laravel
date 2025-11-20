<?php

// Run from artisan tinker or command line with Laravel bootstrap

$shop = new \App\Models\PrestaShopShop();
$shop->name = 'TEST DIAGNOSTIC';
$shop->prestashop_version = '8';
$shop->shop_url = 'https://dev.mpptrade.pl';
$shop->api_key = encrypt('W5FA6JHVUIMM2ETKZZ4XVGZBXQWQPHNN');
$shop->active = true;

$client = new \App\Services\PrestaShop\PrestaShop8Client($shop);

echo "=== CALLING getPriceGroups() ===\n\n";

try {
    $response = $client->getPriceGroups();

    echo "SUCCESS!\n\n";
    echo "=== RAW RESPONSE ===\n";
    echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";

    echo "=== STRUCTURE ANALYSIS ===\n";
    echo "Root keys: " . implode(', ', array_keys($response)) . "\n\n";

    if (isset($response['groups'])) {
        echo "groups exists: YES\n";
        echo "groups type: " . gettype($response['groups']) . "\n";

        if (is_array($response['groups'])) {
            echo "groups count: " . count($response['groups']) . "\n\n";

            if (count($response['groups']) > 0) {
                $firstGroup = $response['groups'][0];
                echo "=== FIRST GROUP ===\n";
                echo "Type: " . gettype($firstGroup) . "\n";
                echo "Keys: " . implode(', ', array_keys($firstGroup)) . "\n\n";

                if (isset($firstGroup['group'])) {
                    echo "STRUCTURE: WRAPPED (has 'group' key)\n";
                    echo "Inner keys: " . implode(', ', array_keys($firstGroup['group'])) . "\n\n";
                    echo "Sample:\n";
                    echo json_encode($firstGroup, JSON_PRETTY_PRINT) . "\n";
                } else {
                    echo "STRUCTURE: DIRECT (no 'group' key)\n\n";
                    echo "Sample:\n";
                    echo json_encode($firstGroup, JSON_PRETTY_PRINT) . "\n";
                }
            }
        }
    }

} catch (\Exception $e) {
    echo "ERROR: {$e->getMessage()}\n";
    echo "File: {$e->getFile()}:{$e->getLine()}\n";
}
