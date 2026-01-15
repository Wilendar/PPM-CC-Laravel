$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
Write-Host "=== Checking category 2354 ===" -ForegroundColor Cyan

Write-Host "`n[1] Checking PrestaShop API directly..." -ForegroundColor Yellow
$cmd1 = @'
cd domains/ppm.mpptrade.pl/public_html && php artisan tinker --execute="
\$shop = App\Models\PrestaShopShop::find(1);
\$client = app(App\Services\PrestaShop\PrestaShopClientFactory::class)->create(\$shop);
try {
    \$cat = \$client->getCategory(2354);
    echo 'Category 2354 EXISTS: ' . json_encode(\$cat);
} catch (Exception \$e) {
    echo 'Error: ' . \$e->getMessage();
}
"
'@
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch $cmd1

Write-Host "`n[2] Checking cache status..." -ForegroundColor Yellow
$cmd2 = @'
cd domains/ppm.mpptrade.pl/public_html && php artisan tinker --execute="
\$key = 'prestashop_categories_shop_1';
\$cached = Cache::get(\$key);
echo 'Cache key: ' . \$key . PHP_EOL;
echo 'Cache exists: ' . (\$cached ? 'YES (' . count(\$cached) . ' items)' : 'NO');
"
'@
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch $cmd2

Write-Host "`n[3] Clearing cache and checking fresh tree..." -ForegroundColor Yellow
$cmd3 = @'
cd domains/ppm.mpptrade.pl/public_html && php artisan tinker --execute="
\$shop = App\Models\PrestaShopShop::find(1);
\$service = app(App\Services\PrestaShop\PrestaShopCategoryService::class);
\$service->clearCache(\$shop);
echo 'Cache cleared!' . PHP_EOL;
\$tree = \$service->getCachedCategoryTree(\$shop);
echo 'Fresh tree has ' . count(\$tree) . ' root categories';
"
'@
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch $cmd3

Write-Host "`n=== Done ===" -ForegroundColor Green
