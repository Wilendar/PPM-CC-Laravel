<?php

/**
 * DIAGNOSTIC SCRIPT: Analyze PrestaShop groups API response structure
 *
 * Purpose: Determine actual JSON structure returned by PrestaShop 8.x /api/groups endpoint
 * to fix parsing error in AddShop.php line 500
 */

// Shop credentials from production logs
$apiKey = 'W5FA6JHVUIMM2ETKZZ4XVGZBXQWQPHNN'; // From dev.mpptrade.pl
$shopUrl = 'https://dev.mpptrade.pl';
$endpoint = '/api/groups?display=full';

echo "=== PRESTASHOP GROUPS API DIAGNOSTIC ===\n\n";
echo "Shop: {$shopUrl}\n";
echo "Endpoint: {$endpoint}\n\n";

try {
    // Make request using cURL (raw PHP, no Laravel)
    $ch = curl_init();

    curl_setopt_array($ch, [
        CURLOPT_URL => $shopUrl . $endpoint,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_USERPWD => $apiKey . ':',
        CURLOPT_HTTPHEADER => [
            'Accept: application/json',
            'Content-Type: application/json',
            'Output-Format: JSON',
        ],
    ]);

    $responseBody = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);

    curl_close($ch);

    if ($error) {
        echo "CURL ERROR: {$error}\n";
        exit(1);
    }

    if ($httpCode !== 200) {
        echo "ERROR: Request failed with status {$httpCode}\n";
        echo "Body: {$responseBody}\n";
        exit(1);
    }

    $json = json_decode($responseBody, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        echo "JSON PARSE ERROR: " . json_last_error_msg() . "\n";
        echo "Raw body: {$responseBody}\n";
        exit(1);
    }

    echo "=== RAW JSON RESPONSE ===\n";
    echo json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";

    echo "=== STRUCTURE ANALYSIS ===\n";

    // Check root keys
    echo "Root keys: " . implode(', ', array_keys($json)) . "\n\n";

    // Analyze 'groups' structure
    if (isset($json['groups'])) {
        echo "groups exists: YES\n";
        echo "groups type: " . gettype($json['groups']) . "\n";

        if (is_array($json['groups'])) {
            echo "groups count: " . count($json['groups']) . "\n\n";

            // Analyze first item
            if (count($json['groups']) > 0) {
                $firstGroup = $json['groups'][0];
                echo "=== FIRST GROUP STRUCTURE ===\n";
                echo "Type: " . gettype($firstGroup) . "\n";
                echo "Keys: " . implode(', ', array_keys($firstGroup)) . "\n\n";

                // Check if 'group' key exists (wrapped structure)
                if (isset($firstGroup['group'])) {
                    echo "STRUCTURE: WRAPPED (has 'group' key)\n";
                    echo "group keys: " . implode(', ', array_keys($firstGroup['group'])) . "\n";
                } else {
                    echo "STRUCTURE: DIRECT (no 'group' key)\n";
                }

                echo "\nFirst group sample:\n";
                echo json_encode($firstGroup, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
            }
        }
    } else {
        echo "groups exists: NO\n";
        echo "This is unusual - PrestaShop should return 'groups' key\n";
    }

    echo "\n=== PARSING RECOMMENDATION ===\n";

    if (isset($json['groups']) && is_array($json['groups']) && count($json['groups']) > 0) {
        $sample = $json['groups'][0];

        if (isset($sample['group'])) {
            echo "Use: \$groupData = \$group['group'];\n";
        } elseif (isset($sample['id']) || isset($sample['@attributes']['id'])) {
            echo "Use: \$groupData = \$group; (direct access)\n";
        } else {
            echo "WARNING: Unexpected structure - manual inspection required\n";
        }
    }

} catch (\Exception $e) {
    echo "EXCEPTION: {$e->getMessage()}\n";
    echo "Trace: {$e->getTraceAsString()}\n";
}
