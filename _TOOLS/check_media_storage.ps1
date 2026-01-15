$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"

Write-Host "=== CHECKING MEDIA STORAGE ===" -ForegroundColor Cyan

Write-Host "`n[1] Storage folders:" -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "ls -la domains/ppm.mpptrade.pl/public_html/storage/app/"

Write-Host "`n[2] Public storage link:" -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "ls -la domains/ppm.mpptrade.pl/public_html/public/storage 2>/dev/null || echo 'Storage link does not exist!'"

Write-Host "`n[3] Media table count:" -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php artisan tinker --execute=\"echo 'Media count: ' . App\\Models\\Media::count();\""

Write-Host "`n[4] Pending jobs in queue:" -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php artisan tinker --execute=\"echo 'Jobs in queue: ' . DB::table('jobs')->count();\""

Write-Host "`n=== DONE ===" -ForegroundColor Green
