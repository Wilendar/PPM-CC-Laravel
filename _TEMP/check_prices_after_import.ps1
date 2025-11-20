$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"

Write-Host "`n=== CHECKING PRICES AFTER IMPORT ===" -ForegroundColor Cyan

Write-Host "`nPrices count for product 11029..." -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php artisan tinker --execute=""echo 'Prices count: ' . \DB::table('product_prices')->where('product_id', 11029)->count();"""

Write-Host "`nPrice details..." -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php _TEMP/diagnose_bug14_deep_analysis.php | grep -A 20 'CHECK 5'"

Write-Host "`nRecent price import logs..." -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && tail -n 100 storage/logs/laravel.log | grep -i 'price import\|mapped prestashop'"

Write-Host "`nDone!" -ForegroundColor Green
