$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"

Write-Host "=== CHECKING prestashop_shops SCHEMA ===" -ForegroundColor Cyan

$schema = plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch @"
cd domains/ppm.mpptrade.pl/public_html && php artisan tinker --execute="
\`$columns = DB::select('DESCRIBE prestashop_shops');
foreach (\`$columns as \`$col) {
    echo \`$col->Field . ' | ' . \`$col->Type . PHP_EOL;
}
"
"@

Write-Host $schema

Write-Host "`n=== CURRENT SHOPS DATA ===" -ForegroundColor Cyan
$shops = plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch @"
cd domains/ppm.mpptrade.pl/public_html && php artisan tinker --execute="
\`$shops = DB::table('prestashop_shops')->get();
echo json_encode(\`$shops, JSON_PRETTY_PRINT);
"
"@

Write-Host $shops
