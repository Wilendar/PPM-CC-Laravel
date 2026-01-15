$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
$RemoteBase = "domains/ppm.mpptrade.pl/public_html"
Write-Host "Clearing ALL caches and checking routes..." -ForegroundColor Cyan

Write-Host "`n[1] Clearing bootstrap cache..." -ForegroundColor Yellow
& plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd $RemoteBase && rm -rf bootstrap/cache/*.php"

Write-Host "`n[2] Clearing all artisan caches..." -ForegroundColor Yellow
& plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd $RemoteBase && php artisan optimize:clear"

Write-Host "`n[3] Checking if admin.product-parameters route exists..." -ForegroundColor Yellow
& plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd $RemoteBase && php artisan route:list --name=admin.product-parameters"

Write-Host "`n[4] Checking products edit route..." -ForegroundColor Yellow
& plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd $RemoteBase && php artisan route:list --path=products"
