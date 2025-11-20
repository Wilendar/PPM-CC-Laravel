$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"

Write-Host "=== CHECKING CATEGORY MAPPINGS FOR SHOPS 1 & 5 ===" -ForegroundColor Cyan

$result = plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php artisan tinker --execute=`"
echo '=== SHOP 1 CATEGORY MAPPINGS ===' . PHP_EOL;
\`$shop1Mappings = DB::table('shop_mappings')
    ->where('shop_id', 1)
    ->where('mapping_type', 'category')
    ->where('is_active', true)
    ->whereIn('ppm_value', ['60', '61'])
    ->get(['ppm_value', 'prestashop_id', 'prestashop_value']);

if (\`$shop1Mappings->isEmpty()) {
    echo '  ⚠️ NO MAPPINGS for categories 60, 61' . PHP_EOL;
} else {
    foreach (\`$shop1Mappings as \`$m) {
        echo '  PPM Category ' . \`$m->ppm_value . ' → PrestaShop ' . \`$m->prestashop_id . ' (' . \`$m->prestashop_value . ')' . PHP_EOL;
    }
}

echo PHP_EOL . '=== SHOP 5 CATEGORY MAPPINGS ===' . PHP_EOL;
\`$shop5Mappings = DB::table('shop_mappings')
    ->where('shop_id', 5)
    ->where('mapping_type', 'category')
    ->where('is_active', true)
    ->whereIn('ppm_value', ['60', '61'])
    ->get(['ppm_value', 'prestashop_id', 'prestashop_value']);

if (\`$shop5Mappings->isEmpty()) {
    echo '  ⚠️ NO MAPPINGS for categories 60, 61' . PHP_EOL;
} else {
    foreach (\`$shop5Mappings as \`$m) {
        echo '  PPM Category ' . \`$m->ppm_value . ' → PrestaShop ' . \`$m->prestashop_id . ' (' . \`$m->prestashop_value . ')' . PHP_EOL;
    }
}

echo PHP_EOL . '=== ALL SHOP 1 CATEGORY MAPPINGS ===' . PHP_EOL;
\`$allShop1 = DB::table('shop_mappings')
    ->where('shop_id', 1)
    ->where('mapping_type', 'category')
    ->where('is_active', true)
    ->get(['ppm_value', 'prestashop_id']);

echo 'Total: ' . \`$allShop1->count() . ' mappings' . PHP_EOL;
foreach (\`$allShop1 as \`$m) {
    echo '  ' . \`$m->ppm_value . ' → ' . \`$m->prestashop_id . PHP_EOL;
}
`""

Write-Host "`n$result" -ForegroundColor White
