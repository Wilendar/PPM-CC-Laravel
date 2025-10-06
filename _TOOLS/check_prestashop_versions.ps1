# Check PrestaShop Shops Version Field
# Debug script for Import UI issue

$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
$HostidoUser = "host379076@host379076.hostido.net.pl"
$HostidoPort = 64321
$RemotePath = "domains/ppm.mpptrade.pl/public_html"

Write-Host "`n=== CHECKING PRESTASHOP_SHOPS VERSION FIELD ===" -ForegroundColor Cyan

# Execute SQL query via artisan tinker
$Query = @"
\$shops = DB::table('prestashop_shops')->select('id', 'shop_name', 'shop_url', 'version')->get();
foreach(\$shops as \$shop) {
    echo sprintf("ID: %d | %s | URL: %s | Version: [%s]\n", \$shop->id, \$shop->shop_name, \$shop->shop_url, \$shop->version ?? 'NULL');
}
"@

$TinkerCommand = "cd $RemotePath && php artisan tinker --execute=`"$Query`""

Write-Host "`nExecuting query..." -ForegroundColor Yellow
plink -ssh $HostidoUser -P $HostidoPort -i $HostidoKey -batch $TinkerCommand

Write-Host "`n=== DONE ===" -ForegroundColor Green
