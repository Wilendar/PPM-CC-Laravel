$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"

Write-Host "=== CHECKING 500 ERROR ===" -ForegroundColor Cyan

Write-Host "`n[1] Last 30 ERROR logs:" -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "tail -100 domains/ppm.mpptrade.pl/public_html/storage/logs/laravel.log | grep -E 'production.ERROR' | tail -10"

Write-Host "`n[2] Last exception details:" -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "tail -50 domains/ppm.mpptrade.pl/public_html/storage/logs/laravel.log | head -30"

Write-Host "`n=== DONE ===" -ForegroundColor Green
