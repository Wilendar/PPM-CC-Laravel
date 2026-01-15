$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"

Write-Host "=== CHECKING STORAGE SETUP ===" -ForegroundColor Cyan

Write-Host "`n[1] Check storage symlink:" -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "ls -la domains/ppm.mpptrade.pl/public_html/public/ | grep storage"

Write-Host "`n[2] Check products folder exists:" -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "ls -la domains/ppm.mpptrade.pl/public_html/storage/app/public/ 2>&1"

Write-Host "`n[3] Clear all caches:" -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php artisan cache:clear && php artisan config:clear && php artisan queue:flush"

Write-Host "`n=== DONE ===" -ForegroundColor Green
