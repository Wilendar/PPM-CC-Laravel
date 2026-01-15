# Deploy shop badges fix
$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
$RemoteBase = "host379076@host379076.hostido.net.pl:domains/ppm.mpptrade.pl/public_html"

Write-Host "=== DEPLOYING SHOP BADGES FIX ===" -ForegroundColor Cyan

Write-Host "[1/4] Uploading ALL assets..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 -r "D:\OneDrive - MPP TRADE\Skrypty\PPM-CC-Laravel\public\build\assets\*" "$RemoteBase/public/build/assets/"

Write-Host "[2/4] Uploading manifest..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 "D:\OneDrive - MPP TRADE\Skrypty\PPM-CC-Laravel\public\build\.vite\manifest.json" "$RemoteBase/public/build/manifest.json"

Write-Host "[3/4] Uploading blade template..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 "D:\OneDrive - MPP TRADE\Skrypty\PPM-CC-Laravel\resources\views\livewire\admin\media\media-manager.blade.php" "$RemoteBase/resources/views/livewire/admin/media/media-manager.blade.php"

Write-Host "[4/4] Clearing cache..." -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php artisan view:clear && php artisan cache:clear"

Write-Host "=== DEPLOYMENT COMPLETE ===" -ForegroundColor Green
