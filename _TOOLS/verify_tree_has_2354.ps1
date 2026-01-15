$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
Write-Host "=== Verifying category 2354 in tree data ===" -ForegroundColor Cyan

$cmd = @'
cd domains/ppm.mpptrade.pl/public_html && php artisan tinker --execute="
\$shop = App\Models\PrestaShopShop::find(1);
\$service = app(App\Services\PrestaShop\PrestaShopCategoryService::class);

// Clear cache first
\$service->clearCache(\$shop);
echo 'Cache cleared.' . PHP_EOL;

// Fetch fresh categories
\$flat = \$service->fetchCategoriesFromShop(\$shop);
echo 'Flat categories count: ' . count(\$flat) . PHP_EOL;

// Check if 2354 is in flat list
\$has2354 = false;
foreach (\$flat as \$cat) {
    if ((\$cat['id'] ?? null) == 2354) {
        \$has2354 = true;
        echo 'Found 2354 in flat: ' . json_encode(\$cat) . PHP_EOL;
        break;
    }
}
if (!\$has2354) {
    echo 'Category 2354 NOT in flat list!' . PHP_EOL;
}

// Build tree and check
\$tree = \$service->buildCategoryTree(\$flat);
echo PHP_EOL . 'Tree root count: ' . count(\$tree) . PHP_EOL;

// Search for 2354 in tree recursively
function findInTree(\$nodes, \$id) {
    foreach (\$nodes as \$node) {
        if ((\$node['id'] ?? null) == \$id) return \$node;
        if (!empty(\$node['children'])) {
            \$found = findInTree(\$node['children'], \$id);
            if (\$found) return \$found;
        }
    }
    return null;
}

\$found2354 = findInTree(\$tree, 2354);
echo 'Category 2354 in tree: ' . (\$found2354 ? 'YES - ' . (\$found2354['name'] ?? 'N/A') : 'NO') . PHP_EOL;
"
'@
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch $cmd

Write-Host "`n=== Done ===" -ForegroundColor Green
