$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
Write-Host "=== Listing PrestaShop categories ===" -ForegroundColor Cyan

$cmd = @'
cd domains/ppm.mpptrade.pl/public_html && php artisan tinker --execute="
\$shop = App\Models\PrestaShopShop::find(1);
\$client = app(App\Services\PrestaShop\PrestaShopClientFactory::class)->create(\$shop);

// Get all categories using correct method
\$categories = \$client->getCategories();
echo 'Total categories: ' . count(\$categories) . PHP_EOL . PHP_EOL;

// Show last 20 categories by ID
\$sorted = collect(\$categories)->sortByDesc('id')->take(20);
foreach (\$sorted as \$cat) {
    echo 'ID: ' . \$cat['id'] . ' | Name: ' . (\$cat['name'] ?? 'N/A') . ' | Parent: ' . (\$cat['id_parent'] ?? 'N/A') . PHP_EOL;
}
"
'@
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch $cmd

Write-Host "`n=== Done ===" -ForegroundColor Green
