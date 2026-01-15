# check_variant_images_debug.ps1
# Add debug logging to check what's being passed to extractCombinationImages

$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"

Write-Host "=== Adding Debug Logging ===" -ForegroundColor Cyan

# Add logging to ShopVariantService.extractCombinationImages
$addLogging = @"
cd domains/ppm.mpptrade.pl/public_html
# Add logging before line 767 (return array_map)
# This will help us see what shopUrl is being passed
php artisan tinker --execute=\"
\\\$product = App\Models\Product::find(11148);
\\\$service = app(App\Services\PrestaShop\ShopVariantService::class);
\\\$result = \\\$service->pullShopVariants(\\\$product, 1);

echo 'Variants count: ' . \\\$result['variants']->count() . PHP_EOL;

if (\\\$result['variants']->count() > 0) {
    \\\$firstVariant = \\\$result['variants']->first();
    echo 'First variant images: ' . json_encode(\\\$firstVariant->images ?? []) . PHP_EOL;
    if (!empty(\\\$firstVariant->images)) {
        \\\$firstImage = \\\$firstVariant->images[0] ?? null;
        if (\\\$firstImage) {
            echo 'First image URL: ' . (\\\$firstImage['url'] ?? 'NO URL') . PHP_EOL;
            echo 'First image thumbnail: ' . (\\\$firstImage['thumbnail_url'] ?? 'NO THUMBNAIL') . PHP_EOL;
        }
    }
}
\"
"@

Write-Host "Running diagnostic..." -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch $addLogging
