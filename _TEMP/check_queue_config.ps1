$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"

Write-Host "=== CHECKING QUEUE CONFIGURATION ===" -ForegroundColor Cyan
Write-Host ""

Write-Host "[1] Checking .env QUEUE_CONNECTION..." -ForegroundColor Yellow
& plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && grep QUEUE .env"

Write-Host "`n[2] Checking Laravel config cache..." -ForegroundColor Yellow
& plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php artisan config:show queue.default 2>&1 | head -20"

Write-Host ""
