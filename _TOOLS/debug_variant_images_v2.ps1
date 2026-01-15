# debug_variant_images_v2.ps1
# Simplified diagnostic for variant images

$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"

Write-Host "=== Variant Images Diagnostic v2 ===" -ForegroundColor Cyan

# Get combination structure
Write-Host "`n1. Getting combination structure:" -ForegroundColor Yellow
$testScript = @"
`$product = App\Models\Product::find(11148);
`$shopData = `$product->dataForShop(1)->first();
`$psProductId = `$shopData->prestashop_product_id;

if (`$psProductId) {
    `$shop = App\Models\PrestaShopShop::find(1);
    `$client = new App\Services\PrestaShop\PrestaShop8Client(`$shop);
    `$combinations = `$client->getCombinations(`$psProductId);

    if (!empty(`$combinations)) {
        `$firstCombo = `$combinations[0];
        echo 'Combination ID: ' . (`$firstCombo['id'] ?? 'N/A') . PHP_EOL;
        echo 'Combination SKU: ' . (`$firstCombo['reference'] ?? 'N/A') . PHP_EOL;

        if (isset(`$firstCombo['associations'])) {
            echo 'Has associations: YES' . PHP_EOL;
            if (isset(`$firstCombo['associations']['images'])) {
                `$images = `$firstCombo['associations']['images'];
                echo 'Images in combination: ' . count(`$images) . PHP_EOL;
                echo 'Images data: ' . json_encode(`$images) . PHP_EOL;
            } else {
                echo 'NO images key in associations' . PHP_EOL;
            }
        } else {
            echo 'NO associations key' . PHP_EOL;
        }
    }
}
"@

plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php artisan tinker --execute=""$testScript"""

# Test URL building
Write-Host "`n2. Testing image URL building:" -ForegroundColor Yellow
$testScript2 = @"
`$shop = App\Models\PrestaShopShop::find(1);
`$shopUrl = `$shop->url;
`$psProductId = 1699;
`$imageId = 23894;

`$baseUrl = rtrim(`$shopUrl, '/');
`$imageUrl = ""{`$baseUrl}/{`$psProductId}-{`$imageId}-small_default.jpg"";

echo 'Shop URL: ' . `$shopUrl . PHP_EOL;
echo 'Base URL: ' . `$baseUrl . PHP_EOL;
echo 'Built image URL: ' . `$imageUrl . PHP_EOL;
"@

plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php artisan tinker --execute=""$testScript2"""

# Verify actual URL works
Write-Host "`n3. Testing actual image URL (HTTP 200):" -ForegroundColor Yellow
$testUrl = "https://dev.mpptrade.pl/1699-23894-small_default.jpg"
Write-Host "Testing URL: $testUrl" -ForegroundColor Gray
try {
    $response = Invoke-WebRequest -Uri $testUrl -Method Head -UseBasicParsing -ErrorAction Stop
    Write-Host "SUCCESS: HTTP $($response.StatusCode)" -ForegroundColor Green
} catch {
    Write-Host "FAILED: $($_.Exception.Message)" -ForegroundColor Red
}
