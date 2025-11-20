$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"

$command = @"
cd domains/ppm.mpptrade.pl/public_html && php artisan tinker --execute="
echo '=== PRICE MAPPINGS TABLE CHECK ===\n';
\`$mappings = \DB::table('prestashop_shop_price_mappings')
    ->where('prestashop_shop_id', 1)
    ->get();
echo 'Mappings count: ' . \`$mappings->count() . '\n';
if (\`$mappings->isNotEmpty()) {
    foreach (\`$mappings as \`$m) {
        echo '  - PS Group ' . \`$m->prestashop_price_group_id . ' (' . \`$m->prestashop_price_group_name . ') -> ' . \`$m->ppm_price_group_name . '\n';
    }
}
"
"@

plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch $command
