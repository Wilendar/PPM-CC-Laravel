$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
Write-Host "=== Checking if ID 2354 exists in category list ===" -ForegroundColor Cyan

$cmd = @'
cd domains/ppm.mpptrade.pl/public_html && php artisan tinker --execute="
\$shop = App\Models\PrestaShopShop::find(1);
\$client = app(App\Services\PrestaShop\PrestaShopClientFactory::class)->create(\$shop);

// Get categories
\$result = \$client->getCategories();
\$categories = \$result['categories'] ?? [];

// Look for 2354
\$found = false;
\$lastIds = [];
foreach (\$categories as \$cat) {
    \$id = \$cat['id'] ?? 0;
    \$lastIds[] = \$id;
    if (\$id == 2354) {
        \$found = true;
        break;
    }
}

echo 'Total categories: ' . count(\$categories) . PHP_EOL;
echo 'Category 2354 in list: ' . (\$found ? 'YES' : 'NO') . PHP_EOL;

// Show highest IDs to understand range
\$lastIds = collect(\$categories)->pluck('id')->sort()->values();
echo 'Highest 10 IDs: ' . \$lastIds->slice(-10)->implode(', ') . PHP_EOL;
echo 'ID range: ' . \$lastIds->first() . ' - ' . \$lastIds->last() . PHP_EOL;
"
'@
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch $cmd

Write-Host "`n=== Done ===" -ForegroundColor Green
