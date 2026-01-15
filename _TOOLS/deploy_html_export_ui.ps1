# Deploy HTML Export UI - ETAP_07f_P4
# Deploys BlockDocumentToHtmlExporter and updated canvas/BlockBuilderCanvas

$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
$RemoteHost = "host379076@host379076.hostido.net.pl"
$RemotePort = "64321"
$RemotePath = "domains/ppm.mpptrade.pl/public_html"
$LocalPath = "D:\OneDrive - MPP TRADE\Skrypty\PPM-CC-Laravel"

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "Deploying HTML Export UI Feature" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan

# 1. Deploy BlockDocumentToHtmlExporter
Write-Host "`n[1/4] Deploying BlockDocumentToHtmlExporter.php..." -ForegroundColor Yellow
$serviceDir = "app/Services/VisualEditor"
pscp -i $HostidoKey -P $RemotePort "$LocalPath/$serviceDir/BlockDocumentToHtmlExporter.php" "${RemoteHost}:$RemotePath/$serviceDir/BlockDocumentToHtmlExporter.php"

# 2. Deploy updated BlockBuilderCanvas.php
Write-Host "`n[2/4] Deploying BlockBuilderCanvas.php with export methods..." -ForegroundColor Yellow
$componentDir = "app/Http/Livewire/Products/VisualDescription/BlockBuilder"
pscp -i $HostidoKey -P $RemotePort "$LocalPath/$componentDir/BlockBuilderCanvas.php" "${RemoteHost}:$RemotePath/$componentDir/BlockBuilderCanvas.php"

# 3. Deploy updated canvas.blade.php
Write-Host "`n[3/4] Deploying canvas.blade.php with Export button..." -ForegroundColor Yellow
$viewDir = "resources/views/livewire/products/visual-description/block-builder"
pscp -i $HostidoKey -P $RemotePort "$LocalPath/$viewDir/canvas.blade.php" "${RemoteHost}:$RemotePath/$viewDir/canvas.blade.php"

# 4. Clear cache
Write-Host "`n[4/4] Clearing cache..." -ForegroundColor Yellow
plink -ssh $RemoteHost -P $RemotePort -i $HostidoKey -batch "cd $RemotePath && php artisan view:clear && php artisan cache:clear"

Write-Host "`n========================================" -ForegroundColor Green
Write-Host "Deployment completed!" -ForegroundColor Green
Write-Host "========================================" -ForegroundColor Green
Write-Host "`nTest: Open Block Builder, import HTML, then click 'Export' button" -ForegroundColor Cyan
