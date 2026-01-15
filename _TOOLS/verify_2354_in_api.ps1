$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
Write-Host "=== Verifying category 2354 in API ===" -ForegroundColor Cyan

$cmd = @'
cd domains/ppm.mpptrade.pl/public_html && php artisan tinker --execute="
\$shop = App\Models\PrestaShopShop::find(1);
\$client = app(App\Services\PrestaShop\PrestaShopClientFactory::class)->create(\$shop);

// Check if 2354 is now in the list
\$result = \$client->getCategories();
\$categories = \$result['categories'] ?? [];
\$ids = collect(\$categories)->pluck('id')->toArray();

echo 'Total categories: ' . count(\$ids) . PHP_EOL;
echo 'Category 2354 in list: ' . (in_array(2354, \$ids) ? 'YES' : 'NO') . PHP_EOL;

// Show highest IDs
\$sorted = collect(\$ids)->sort()->values();
echo 'Highest 5 IDs: ' . \$sorted->slice(-5)->implode(', ') . PHP_EOL;

// Get full details for 2354
echo PHP_EOL . '=== Full category 2354 details ===' . PHP_EOL;
\$cat = \$client->getCategory(2354);
print_r(\$cat);
"
'@
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch $cmd

Write-Host "`n=== Done ===" -ForegroundColor Green
