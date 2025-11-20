$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"

Write-Host "=== ANALYZING PRODUCT PB-KAYO-E-KMB CATEGORIES ===" -ForegroundColor Cyan

$result = plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php artisan tinker --execute=`"
\`$product = App\Models\Product::where('sku', 'PB-KAYO-E-KMB')->first();

echo '=== PPM CATEGORIES WITH HIERARCHY ===' . PHP_EOL . PHP_EOL;

// Categories for Shop 5 (test_kayoshop)
\`$shop5Cats = [60, 61];
foreach (\`$shop5Cats as \`$catId) {
    \`$cat = App\Models\Category::find(\`$catId);
    if (\`$cat) {
        echo 'Category ' . \`$catId . ': ' . \`$cat->name . PHP_EOL;
        echo '  Parent: ' . (\`$cat->parent_id ?? 'NULL') . PHP_EOL;

        // Get full hierarchy
        \`$parents = [];
        \`$current = \`$cat;
        while (\`$current->parent_id) {
            \`$parent = App\Models\Category::find(\`$current->parent_id);
            if (\`$parent) {
                \`$parents[] = \`$parent->name . ' (ID: ' . \`$parent->id . ')';
                \`$current = \`$parent;
            } else {
                break;
            }
        }

        if (!empty(\`$parents)) {
            echo '  Full hierarchy: ' . implode(' > ', array_reverse(\`$parents)) . ' > ' . \`$cat->name . PHP_EOL;
        }

        // Check mapping
        \`$mapping = DB::table('shop_mappings')
            ->where('shop_id', 5)
            ->where('mapping_type', 'category')
            ->where('ppm_value', \`$catId)
            ->where('is_active', true)
            ->first();

        echo '  PrestaShop mapping: ' . (\`$mapping ? 'ID ' . \`$mapping->prestashop_id : 'NOT MAPPED') . PHP_EOL;
        echo PHP_EOL;
    }
}

echo PHP_EOL . '=== PRIMARY CATEGORY STATUS ===' . PHP_EOL;
\`$primary = DB::table('product_categories')
    ->where('product_id', 11033)
    ->where('shop_id', 5)
    ->where('is_primary', true)
    ->first();

if (\`$primary) {
    \`$primaryCat = App\Models\Category::find(\`$primary->category_id);
    echo 'Primary category: ' . \`$primaryCat->name . ' (ID: ' . \`$primary->category_id . ')' . PHP_EOL;
}
`""

Write-Host "`n$result" -ForegroundColor White
