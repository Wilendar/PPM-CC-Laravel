$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"

Write-Host "=== CHECKING FAIL REASON ===" -ForegroundColor Cyan

Write-Host "`n[1] Last 50 ERROR logs:" -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "tail -200 domains/ppm.mpptrade.pl/public_html/storage/logs/laravel.log | grep -E 'production.ERROR' | tail -10"

Write-Host "`n[2] Failed jobs table:" -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php artisan queue:failed"

Write-Host "`n=== DONE ===" -ForegroundColor Green
