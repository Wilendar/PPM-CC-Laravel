$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"

Write-Host "=== CHECKING DISPATCH CONTEXT ===" -ForegroundColor Cyan

Write-Host "`n[1] Lines 835-850 of BulkImportProducts.php (where dispatchMediaSync is called):" -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "sed -n '835,850p' domains/ppm.mpptrade.pl/public_html/app/Jobs/PrestaShop/BulkImportProducts.php"

Write-Host "`n[2] Search for 'Media sync' in logs:" -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "grep -i 'media sync' domains/ppm.mpptrade.pl/public_html/storage/logs/laravel.log | tail -10"

Write-Host "`n[3] Check queue driver:" -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "grep QUEUE_CONNECTION domains/ppm.mpptrade.pl/public_html/.env"

Write-Host "`n[4] Check jobs table:" -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && mysql -u host379076_ppm -p'j8v3P_gT@lmB' host379076_ppm -e 'SELECT COUNT(*) as pending_jobs FROM jobs;'"

Write-Host "`n=== DONE ===" -ForegroundColor Green
