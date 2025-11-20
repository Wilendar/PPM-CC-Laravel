$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"

$command = @"
cd domains/ppm.mpptrade.pl/public_html && php artisan tinker --execute="
echo '=== SHOP MAPPINGS CHECK ===\n';
\`$shop = \App\Models\PrestaShopShop::first();
if (\`$shop) {
    echo 'Shop: ' . \`$shop->name . ' (ID: ' . \`$shop->id . ')\n';
    echo 'Price mappings (type): ' . gettype(\`$shop->price_group_mappings) . '\n';
    echo 'Count: ' . (is_array(\`$shop->price_group_mappings) ? count(\`$shop->price_group_mappings) : 0) . '\n';
    echo 'Warehouse mappings (type): ' . gettype(\`$shop->warehouse_mappings) . '\n';
    echo 'Count: ' . (is_array(\`$shop->warehouse_mappings) ? count(\`$shop->warehouse_mappings) : 0) . '\n';
} else {
    echo 'No shops found';
}
"
"@

plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch $command
