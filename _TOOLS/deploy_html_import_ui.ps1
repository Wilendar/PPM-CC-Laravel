# Deploy HTML Import UI - ETAP_07f_P4
# Deploys updated canvas.blade.php and BlockBuilderCanvas with Import HTML feature

$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
$RemoteHost = "host379076@host379076.hostido.net.pl"
$RemotePort = "64321"
$RemotePath = "domains/ppm.mpptrade.pl/public_html"
$LocalPath = "D:\OneDrive - MPP TRADE\Skrypty\PPM-CC-Laravel"

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "Deploying HTML Import UI Feature" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan

# 1. Deploy updated canvas.blade.php
Write-Host "`n[1/3] Deploying canvas.blade.php with Import HTML button..." -ForegroundColor Yellow
$viewDir = "resources/views/livewire/products/visual-description/block-builder"
pscp -i $HostidoKey -P $RemotePort "$LocalPath/$viewDir/canvas.blade.php" "${RemoteHost}:$RemotePath/$viewDir/canvas.blade.php"

# 2. Deploy updated BlockBuilderCanvas.php
Write-Host "`n[2/3] Deploying BlockBuilderCanvas.php with importHtmlContent method..." -ForegroundColor Yellow
$componentDir = "app/Http/Livewire/Products/VisualDescription/BlockBuilder"
pscp -i $HostidoKey -P $RemotePort "$LocalPath/$componentDir/BlockBuilderCanvas.php" "${RemoteHost}:$RemotePath/$componentDir/BlockBuilderCanvas.php"

# 3. Clear cache
Write-Host "`n[3/3] Clearing cache..." -ForegroundColor Yellow
plink -ssh $RemoteHost -P $RemotePort -i $HostidoKey -batch "cd $RemotePath && php artisan view:clear && php artisan cache:clear"

Write-Host "`n========================================" -ForegroundColor Green
Write-Host "Deployment completed!" -ForegroundColor Green
Write-Host "========================================" -ForegroundColor Green
Write-Host "`nTest: Open Block Builder and click 'Import HTML' button" -ForegroundColor Cyan
