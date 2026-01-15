$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
Write-Host "=== Testing getShopCategories() output ===" -ForegroundColor Cyan

$cmd = @'
cd domains/ppm.mpptrade.pl/public_html && php artisan tinker --execute="
// Simulate what ProductForm does
\$shop = App\Models\PrestaShopShop::find(1);
\$categoryService = app(App\Services\PrestaShop\PrestaShopCategoryService::class);

// Clear cache first
\$categoryService->clearCache(\$shop);

// Get tree (this is what getShopCategories calls)
\$tree = \$categoryService->getCachedCategoryTree(\$shop);

echo 'Tree count: ' . count(\$tree) . PHP_EOL;

// Search for 2354 recursively in tree
function searchTree(\$nodes, \$id, \$path = '') {
    foreach (\$nodes as \$node) {
        \$currentPath = \$path . '/' . (\$node['name'] ?? 'N/A');
        if ((\$node['id'] ?? null) == \$id) {
            return \$currentPath;
        }
        if (!empty(\$node['children'])) {
            \$found = searchTree(\$node['children'], \$id, \$currentPath);
            if (\$found) return \$found;
        }
    }
    return null;
}

\$path2354 = searchTree(\$tree, 2354);
echo 'Category 2354 path: ' . (\$path2354 ?: 'NOT FOUND') . PHP_EOL;

// Show first level categories
echo PHP_EOL . 'Root categories:' . PHP_EOL;
foreach (\$tree as \$cat) {
    echo '- ' . (\$cat['id'] ?? '?') . ': ' . (\$cat['name'] ?? '?') . PHP_EOL;
}

// Show children of 'Wszystko' (id=2)
echo PHP_EOL . 'Children of Wszystko (direct):' . PHP_EOL;
foreach (\$tree as \$root) {
    if (!empty(\$root['children'])) {
        foreach (\$root['children'] as \$child) {
            if ((\$child['name'] ?? '') === 'Wszystko') {
                if (!empty(\$child['children'])) {
                    foreach (\$child['children'] as \$grandchild) {
                        echo '- ' . (\$grandchild['id'] ?? '?') . ': ' . (\$grandchild['name'] ?? '?') . PHP_EOL;
                    }
                }
            }
        }
    }
}
"
'@
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch $cmd

Write-Host "`n=== Done ===" -ForegroundColor Green
