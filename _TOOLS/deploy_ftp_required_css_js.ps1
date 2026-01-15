$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
$RemoteHost = "host379076@host379076.hostido.net.pl"
$RemotePort = "64321"
$RemotePath = "domains/ppm.mpptrade.pl/public_html"
$LocalPath = "D:\OneDrive - MPP TRADE\Skrypty\PPM-CC-Laravel"

Write-Host "Deploying FTP required for CSS/JS scan changes..." -ForegroundColor Cyan

# Upload AddShop.php
$phpFile = "app/Http/Livewire/Admin/Shops/AddShop.php"
$localFile = Join-Path $LocalPath $phpFile
$remoteFile = "$RemotePath/$phpFile"
Write-Host "Uploading $phpFile..." -ForegroundColor Yellow
pscp -i $HostidoKey -P $RemotePort $localFile "${RemoteHost}:$remoteFile"

# Upload add-shop.blade.php
$bladeFile = "resources/views/livewire/admin/shops/add-shop.blade.php"
$localFile = Join-Path $LocalPath $bladeFile
$remoteFile = "$RemotePath/$bladeFile"
Write-Host "Uploading $bladeFile..." -ForegroundColor Yellow
pscp -i $HostidoKey -P $RemotePort $localFile "${RemoteHost}:$remoteFile"

# Clear caches
Write-Host ""
Write-Host "Clearing caches..." -ForegroundColor Yellow
plink -ssh $RemoteHost -P $RemotePort -i $HostidoKey -batch "cd $RemotePath && php artisan view:clear && php artisan cache:clear"

Write-Host ""
Write-Host "Deployment complete!" -ForegroundColor Green
