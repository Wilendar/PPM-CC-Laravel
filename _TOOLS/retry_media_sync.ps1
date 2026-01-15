$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"

Write-Host "=== RETRY MEDIA SYNC ===" -ForegroundColor Cyan

Write-Host "`n[1] Clear failed jobs:" -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php artisan queue:flush && php artisan cache:clear"

Write-Host "`n=== DONE - uruchom import ponownie ===" -ForegroundColor Green
