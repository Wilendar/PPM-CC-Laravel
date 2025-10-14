# Quick CSS Upload Script
$ErrorActionPreference = "Stop"

$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
$LocalCSS = "D:\OneDrive - MPP TRADE\Skrypty\PPM-CC-Laravel\public\build\assets\category-form-CBqfE0rW.css"
$LocalManifest = "D:\OneDrive - MPP TRADE\Skrypty\PPM-CC-Laravel\public\build\.vite\manifest.json"
$RemoteHost = "host379076@host379076.hostido.net.pl"
$RemoteBase = "domains/ppm.mpptrade.pl/public_html/public/build"

Write-Host "`n=== QUICK CSS UPLOAD ===" -ForegroundColor Cyan

# Upload new CSS
Write-Host "`nUploading category-form-CBqfE0rW.css..." -ForegroundColor Yellow
& pscp -i $HostidoKey -P 64321 $LocalCSS "${RemoteHost}:${RemoteBase}/assets/category-form-CBqfE0rW.css"

# Upload updated manifest
Write-Host "`nUploading manifest.json..." -ForegroundColor Yellow
& pscp -i $HostidoKey -P 64321 $LocalManifest "${RemoteHost}:${RemoteBase}/.vite/manifest.json"
& pscp -i $HostidoKey -P 64321 $LocalManifest "${RemoteHost}:${RemoteBase}/manifest.json"

# Clear cache
Write-Host "`nClearing Laravel cache..." -ForegroundColor Yellow
& plink -ssh $RemoteHost -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php artisan view:clear && php artisan cache:clear"

Write-Host "`n=== UPLOAD COMPLETE ===" -ForegroundColor Green