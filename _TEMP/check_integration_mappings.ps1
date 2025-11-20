$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"

$command = @"
cd domains/ppm.mpptrade.pl/public_html && php artisan tinker --execute="
echo '=== INTEGRATION MAPPINGS TABLE ===\n';
\`$mappings = \DB::table('integration_mappings')
    ->where('integration_type', 'prestashop')
    ->where('integration_identifier', 1)
    ->get();
echo 'Mappings count: ' . \`$mappings->count() . '\n';
if (\`$mappings->isNotEmpty()) {
    foreach (\`$mappings as \`$m) {
        echo '  - ' . \`$m->mapping_type . ': ' . \`$m->ppm_id . ' -> ' . \`$m->external_id . '\n';
    }
}
"
"@

plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch $command
