$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"

Write-Host "=== CHECKING IF DISPATCH CODE EXISTS ===" -ForegroundColor Cyan

Write-Host "`n[1] Check for dispatchMediaSync in BulkImportProducts.php:" -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "grep -n 'dispatchMediaSync' domains/ppm.mpptrade.pl/public_html/app/Jobs/PrestaShop/BulkImportProducts.php"

Write-Host "`n[2] Check for SyncMediaFromPrestaShop import:" -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "grep -n 'SyncMediaFromPrestaShop' domains/ppm.mpptrade.pl/public_html/app/Jobs/PrestaShop/BulkImportProducts.php"

Write-Host "`n[3] Check jobs table count:" -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && echo 'SELECT COUNT(*) as cnt FROM jobs;' | php artisan tinker 2>/dev/null | grep -E '[0-9]+'"

Write-Host "`n[4] Latest laravel.log entries (last 50 lines):" -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "tail -50 domains/ppm.mpptrade.pl/public_html/storage/logs/laravel.log | grep -E '(Error|Exception|import|Import)' | tail -10"

Write-Host "`n=== DONE ===" -ForegroundColor Green
