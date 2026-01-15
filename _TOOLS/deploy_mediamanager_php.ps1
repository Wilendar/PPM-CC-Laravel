# Deploy MediaManager.php with new properties
$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
$RemoteBase = "host379076@host379076.hostido.net.pl:domains/ppm.mpptrade.pl/public_html"

Write-Host "=== DEPLOYING MediaManager.php ===" -ForegroundColor Cyan

Write-Host "[1/2] Uploading MediaManager.php..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 "app/Http/Livewire/Admin/Media/MediaManager.php" "$RemoteBase/app/Http/Livewire/Admin/Media/MediaManager.php"

Write-Host "[2/2] Clearing cache..." -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php artisan view:clear && php artisan cache:clear"

Write-Host "=== DONE ===" -ForegroundColor Green
