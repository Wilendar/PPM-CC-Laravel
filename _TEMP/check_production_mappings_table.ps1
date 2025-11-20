$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"

Write-Host "`n=== CHECKING PRODUCTION DATABASE ===" -ForegroundColor Cyan

Write-Host "`nChecking if prestashop_shop_price_mappings table exists..." -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php artisan tinker --execute=`"echo \Schema::hasTable('prestashop_shop_price_mappings') ? 'TABLE EXISTS' : 'TABLE MISSING';`""

Write-Host "`nChecking for mappings..." -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php artisan tinker --execute=`"echo 'Mappings count: ' . \DB::table('prestashop_shop_price_mappings')->count();`""

Write-Host "`nDone!" -ForegroundColor Green
