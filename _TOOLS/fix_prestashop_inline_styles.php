<?php
/**
 * Fix inline styles on PrestaShop product 4016
 * Run on production: php fix_prestashop_inline_styles.php
 */

require_once __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\PrestaShopShop;
use Illuminate\Support\Facades\Http;

$productId = 4016;
$shopId = 5;

echo "=== Fix Inline Styles for PrestaShop Product $productId ===\n\n";

// Get shop
$shop = PrestaShopShop::find($shopId);
if (!$shop) {
    echo "ERROR: Shop $shopId not found\n";
    exit(1);
}

echo "Shop: {$shop->name}\n";
echo "URL: {$shop->url}\n";

// Build API URL - use api_url or construct from url
$apiUrl = $shop->api_url;
if (empty($apiUrl)) {
    $apiUrl = rtrim($shop->url, '/') . '/api';
}
$apiUrl = rtrim($apiUrl, '/');
$apiKey = $shop->api_key;

// Fetch product
echo "Fetching product $productId from PrestaShop API...\n";

$url = "{$apiUrl}/products/{$productId}?output_format=JSON";
$response = Http::withBasicAuth($apiKey, '')
    ->accept('application/json')
    ->get($url);

if (!$response->successful()) {
    echo "ERROR: API request failed - " . $response->status() . "\n";
    echo $response->body() . "\n";
    exit(1);
}

$data = $response->json();
$product = $data['product'] ?? null;

if (!$product) {
    echo "ERROR: Product not found in response\n";
    exit(1);
}

echo "Product found: " . ($product['name'][0]['value'] ?? 'N/A') . "\n\n";

// Process descriptions - handle both array and multilang format
$descriptions = $product['description'] ?? [];
$modified = false;
$cleanDescriptions = [];

// Normalize descriptions to array format
if (is_string($descriptions)) {
    // Single language - wrap in array
    $descriptions = [['id' => 1, 'value' => $descriptions]];
} elseif (isset($descriptions['language'])) {
    // Multilang format with 'language' key
    $descriptions = is_array($descriptions['language'][0] ?? null)
        ? $descriptions['language']
        : [$descriptions['language']];
}

foreach ($descriptions as $i => $desc) {
    // Handle different formats
    $langId = $desc['@attributes']['id'] ?? $desc['id'] ?? 1;
    $html = $desc['#text'] ?? $desc['value'] ?? (is_string($desc) ? $desc : '');

    if (empty($html)) {
        $cleanDescriptions[] = $desc;
        continue;
    }

    // Check for inline styles
    $styleCount = preg_match_all('/\s*style=["\'][^"\']*["\']/', $html);

    if ($styleCount > 0) {
        echo "Language $langId: Found $styleCount inline styles\n";

        $originalLength = strlen($html);

        // Remove inline styles
        $cleanHtml = preg_replace('/\s*style=["\'][^"\']*["\']/', '', $html);

        $cleanLength = strlen($cleanHtml);
        $saved = $originalLength - $cleanLength;

        echo "  Original: $originalLength chars\n";
        echo "  Clean: $cleanLength chars\n";
        echo "  Saved: $saved chars (" . round($saved/$originalLength*100, 1) . "%)\n\n";

        // Build clean description entry
        $cleanDescriptions[] = ['id' => $langId, 'value' => $cleanHtml];
        $modified = true;
    } else {
        $cleanDescriptions[] = ['id' => $langId, 'value' => $html];
    }
}

$product['description'] = $cleanDescriptions;

if (!$modified) {
    echo "No inline styles found in descriptions.\n";
    exit(0);
}

// Build XML for update - include required fields
echo "Building update XML...\n";

$xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><prestashop xmlns:xlink="http://www.w3.org/1999/xlink"/>');
$productXml = $xml->addChild('product');
$productXml->addChild('id', $productId);

// Required fields - copy from original product
$productXml->addChild('price', $product['price'] ?? '0');
$productXml->addChild('id_category_default', $product['id_category_default'] ?? '2');
$productXml->addChild('id_shop_default', $product['id_shop_default'] ?? '1');
$productXml->addChild('active', $product['active'] ?? '1');

// Name (multilang)
$nameXml = $productXml->addChild('name');
$names = $product['name'] ?? [];

// Debug name format
echo "Name format: " . gettype($names) . "\n";
if (is_array($names)) {
    echo "Name keys: " . implode(', ', array_keys($names)) . "\n";
}

// Normalize name to array of language entries
if (is_string($names)) {
    $names = [['id' => 1, 'value' => $names]];
} elseif (isset($names['language'])) {
    $langData = $names['language'];
    if (isset($langData['@attributes'])) {
        // Single language
        $names = [['id' => $langData['@attributes']['id'] ?? 1, 'value' => $langData['#text'] ?? '']];
    } elseif (is_array($langData)) {
        // Multiple languages
        $names = array_map(function($l) {
            return ['id' => $l['@attributes']['id'] ?? $l['id'] ?? 1, 'value' => $l['#text'] ?? $l['value'] ?? ''];
        }, $langData);
    }
} elseif (!is_array($names) || !isset($names[0])) {
    $names = [['id' => 1, 'value' => 'Product']];
}

foreach ($names as $name) {
    $langId = $name['id'] ?? 1;
    $value = $name['value'] ?? '';
    $lang = $nameXml->addChild('language', htmlspecialchars($value));
    $lang->addAttribute('id', $langId);
}

// Link rewrite (multilang) - required
$linkRewriteXml = $productXml->addChild('link_rewrite');
$linkRewrites = $product['link_rewrite'] ?? [];

// Normalize link_rewrite to array of language entries
if (is_string($linkRewrites)) {
    $linkRewrites = [['id' => 1, 'value' => $linkRewrites]];
} elseif (isset($linkRewrites['language'])) {
    $langData = $linkRewrites['language'];
    if (isset($langData['@attributes'])) {
        $linkRewrites = [['id' => $langData['@attributes']['id'] ?? 1, 'value' => $langData['#text'] ?? '']];
    } elseif (is_array($langData)) {
        $linkRewrites = array_map(function($l) {
            return ['id' => $l['@attributes']['id'] ?? $l['id'] ?? 1, 'value' => $l['#text'] ?? $l['value'] ?? ''];
        }, $langData);
    }
} elseif (!is_array($linkRewrites) || !isset($linkRewrites[0])) {
    $linkRewrites = [['id' => 1, 'value' => 'product']];
}

foreach ($linkRewrites as $lr) {
    $langId = $lr['id'] ?? 1;
    $value = $lr['value'] ?? '';
    $lang = $linkRewriteXml->addChild('language', htmlspecialchars($value));
    $lang->addAttribute('id', $langId);
}

// Description (multilang) - the cleaned one
$descXml = $productXml->addChild('description');
foreach ($product['description'] as $desc) {
    $lang = $descXml->addChild('language', '');
    $lang->addAttribute('id', $desc['id']);
    // Use CDATA for HTML content
    $node = dom_import_simplexml($lang);
    $cdata = $node->ownerDocument->createCDATASection($desc['value']);
    $node->appendChild($cdata);
}

// Update via API
echo "Updating product on PrestaShop...\n";

$updateUrl = "{$apiUrl}/products/{$productId}";
$xmlString = $xml->asXML();

$updateResponse = Http::withBasicAuth($apiKey, '')
    ->withHeaders([
        'Content-Type' => 'text/xml',
    ])
    ->withBody($xmlString, 'text/xml')
    ->put($updateUrl);

if ($updateResponse->successful()) {
    echo "\nSUCCESS! Inline styles removed from product $productId\n";
} else {
    echo "\nERROR: Update failed - " . $updateResponse->status() . "\n";
    echo $updateResponse->body() . "\n";
    exit(1);
}

echo "\nDone! Refresh the PrestaShop page to see changes.\n";
