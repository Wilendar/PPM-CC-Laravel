$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"

Write-Host "=== CHECKING PENDING JOBS ===" -ForegroundColor Cyan

Write-Host "`n[1] Queue configuration:" -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && grep -E 'QUEUE_CONNECTION' .env"

Write-Host "`n[2] Pending jobs count:" -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && mysql -e 'SELECT COUNT(*) AS pending_jobs FROM jobs;' host379076_ppm"

Write-Host "`n[3] Run ONE job now (work --once):" -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php artisan queue:work --once --verbose"

Write-Host "`n=== DONE ===" -ForegroundColor Green
