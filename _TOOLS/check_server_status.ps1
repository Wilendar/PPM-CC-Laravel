$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
$RemoteBase = "domains/ppm.mpptrade.pl/public_html"

Write-Host "Checking server status..." -ForegroundColor Cyan

Write-Host "`n[1] Check if web.php exists and is readable..." -ForegroundColor Yellow
& plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "ls -la $RemoteBase/routes/web.php"

Write-Host "`n[2] Check web.php syntax..." -ForegroundColor Yellow
& plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd $RemoteBase && php -l routes/web.php"

Write-Host "`n[3] Check if artisan works..." -ForegroundColor Yellow
& plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd $RemoteBase && php artisan --version"

Write-Host "`n[4] Check routes..." -ForegroundColor Yellow
& plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd $RemoteBase && php artisan route:list --path=products | head -5"
