$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
Write-Host "=== Debug categories structure ===" -ForegroundColor Cyan

$cmd = @'
cd domains/ppm.mpptrade.pl/public_html && php artisan tinker --execute="
\$shop = App\Models\PrestaShopShop::find(1);
\$client = app(App\Services\PrestaShop\PrestaShopClientFactory::class)->create(\$shop);

// Get categories and show raw structure
\$categories = \$client->getCategories();
echo 'Type: ' . gettype(\$categories) . PHP_EOL;
echo 'Count: ' . count(\$categories) . PHP_EOL;
echo 'First 3 items: ' . PHP_EOL;
print_r(array_slice(\$categories, 0, 3));
"
'@
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch $cmd

Write-Host "`n=== Done ===" -ForegroundColor Green
