$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"

Write-Host "=== CHECKING CATEGORY SYNC LOGS FOR PRODUCT 11033 ===" -ForegroundColor Cyan

$result = plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && tail -500 storage/logs/laravel.log | grep 'CATEGORY SYNC' | grep '11033'"

Write-Host "`n$result`n" -ForegroundColor White

Write-Host "`n=== CHECKING PIVOT TABLE DATA FOR PRODUCT 11033 ===" -ForegroundColor Cyan

$pivotCheck = plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php artisan tinker --execute=`"
\`$data = DB::table('product_categories')
    ->where('product_id', 11033)
    ->orderBy('shop_id')
    ->orderBy('category_id')
    ->get(['category_id', 'shop_id', 'is_primary', 'sort_order']);

echo 'Product 11033 categories in pivot table:' . PHP_EOL;
foreach (\`$data as \`$row) {
    echo '  Category: ' . \`$row->category_id . ', Shop: ' . (\`$row->shop_id ?? 'NULL (default)') . ', Primary: ' . (\`$row->is_primary ? 'YES' : 'NO') . ', Sort: ' . \`$row->sort_order . PHP_EOL;
}

echo PHP_EOL . 'Shop 5 specific categories:' . PHP_EOL;
\`$shop5 = DB::table('product_categories')
    ->where('product_id', 11033)
    ->where('shop_id', 5)
    ->get();

echo 'Count: ' . \`$shop5->count() . PHP_EOL;
`""

Write-Host "`n$pivotCheck" -ForegroundColor White
