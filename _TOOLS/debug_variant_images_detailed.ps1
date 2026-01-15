# debug_variant_images_detailed.ps1
# Deep dive into variant images data structure

$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"

Write-Host "=== Deep Dive: Variant Images Data Structure ===" -ForegroundColor Cyan

# Test extractCombinationImages with sample data
Write-Host "`n1. Testing extractCombinationImages with sample combination:" -ForegroundColor Yellow
$testScript = @'
$service = app(\App\Services\PrestaShop\ShopVariantService::class);
$shop = \App\Models\PrestaShopShop::find(1);
$product = \App\Models\Product::find(11148);

// Get actual combinations from PrestaShop
$shopData = $product->dataForShop(1)->first();
$psProductId = $shopData->prestashop_product_id;

if ($psProductId) {
    $client = new \App\Services\PrestaShop\PrestaShop8Client($shop);
    $combinations = $client->getCombinations($psProductId);

    if (!empty($combinations)) {
        $firstCombo = $combinations[0];
        echo "=== First Combination Structure ===\n";
        echo "ID: " . ($firstCombo['id'] ?? 'N/A') . "\n";
        echo "Reference: " . ($firstCombo['reference'] ?? 'N/A') . "\n";
        echo "Has associations: " . (isset($firstCombo['associations']) ? 'YES' : 'NO') . "\n";

        if (isset($firstCombo['associations']['images'])) {
            echo "Images count: " . count($firstCombo['associations']['images']) . "\n";
            echo "Images structure: " . json_encode($firstCombo['associations']['images'], JSON_PRETTY_PRINT) . "\n";
        } else {
            echo "NO IMAGES in associations\n";
        }

        // Try to extract images
        echo "\n=== Testing extractCombinationImages ===\n";
        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('extractCombinationImages');
        $method->setAccessible(true);

        $result = $method->invoke($service, $firstCombo, $shop->url, $psProductId, []);
        echo "Extracted images: " . json_encode($result, JSON_PRETTY_PRINT) . "\n";
    }
}
'@

plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php artisan tinker --execute='$testScript'"

Write-Host "`n2. Check product images as fallback:" -ForegroundColor Yellow
$testScript2 = @'
$product = \App\Models\Product::find(11148);
$shopData = $product->dataForShop(1)->first();
$psProductId = $shopData->prestashop_product_id;

if ($psProductId) {
    $shop = \App\Models\PrestaShopShop::find(1);
    $client = new \App\Services\PrestaShop\PrestaShop8Client($shop);

    $productImages = $client->getProductImages($psProductId);
    echo "Product images count: " . count($productImages) . "\n";
    if (!empty($productImages)) {
        echo "First product image: " . json_encode($productImages[0], JSON_PRETTY_PRINT) . "\n";
    }
}
'@

plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php artisan tinker --execute='$testScript2'"
