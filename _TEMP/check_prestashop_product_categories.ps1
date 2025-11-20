$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"

Write-Host "=== CHECKING PRESTASHOP CATEGORIES FOR PRODUCT PB-KAYO-E-KMB ===" -ForegroundColor Cyan

$query = "SELECT p.id_product, p.reference, p.id_category_default, cp.id_category, c.id_parent, c.level_depth FROM ps_product p LEFT JOIN ps_category_product cp ON p.id_product = cp.id_product LEFT JOIN ps_category c ON cp.id_category = c.id_category WHERE p.reference = 'PB-KAYO-E-KMB' ORDER BY cp.id_category;"

# Try via Laravel Tinker (safer, uses existing PPM app config)
$result = plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php artisan tinker --execute=`"
\`$product = App\Models\Product::where('sku', 'PB-KAYO-E-KMB')->first();
if (\`$product) {
    echo 'Product ID: ' . \`$product->id . PHP_EOL;
    echo 'SKU: ' . \`$product->sku . PHP_EOL;

    // Check categories from pivot table
    \`$cats = DB::table('product_categories')
        ->where('product_id', \`$product->id)
        ->whereNotNull('shop_id')
        ->get(['category_id', 'shop_id', 'is_primary']);

    echo 'Categories in PPM (per-shop):' . PHP_EOL;
    foreach (\`$cats as \`$cat) {
        echo '  Category ID: ' . \`$cat->category_id . ', Shop: ' . \`$cat->shop_id . ', Primary: ' . (\`$cat->is_primary ? 'YES' : 'NO') . PHP_EOL;
    }
} else {
    echo 'Product not found!' . PHP_EOL;
}
`""

Write-Host "`nRESULT:" -ForegroundColor Green
Write-Host $result
