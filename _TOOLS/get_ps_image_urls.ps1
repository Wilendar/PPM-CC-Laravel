# get_ps_image_urls.ps1
# Get actual image URLs from PrestaShop API

$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"

Write-Host "=== Getting PrestaShop Image URLs ===" -ForegroundColor Cyan

$query = @"
`$shop = App\Models\PrestaShopShop::find(1);
`$client = new App\Services\PrestaShop\PrestaShop8Client(`$shop);
`$productImages = `$client->getProductImages(8594);

echo 'Product images count: ' . count(`$productImages) . PHP_EOL;

if (!empty(`$productImages)) {
    `$firstImage = `$productImages[0];
    echo 'First image structure: ' . json_encode(`$firstImage, JSON_PRETTY_PRINT) . PHP_EOL;
}

// Also get combinations with images
`$combinations = `$client->getCombinations(8594);
if (!empty(`$combinations)) {
    `$firstCombo = `$combinations[0];
    echo PHP_EOL . 'First combination images: ' . PHP_EOL;
    if (isset(`$firstCombo['associations']['images'])) {
        echo json_encode(`$firstCombo['associations']['images'], JSON_PRETTY_PRINT) . PHP_EOL;
    } else {
        echo 'NO images in combination associations' . PHP_EOL;
    }
}
"@

plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php artisan tinker --execute=""$query"""
