# Deploy Layout Elements - ETAP_07f_P4 FAZA 4.2.2-4.2.3
# Deploys Column, Grid, Background elements for Block Builder

$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
$RemoteHost = "host379076@host379076.hostido.net.pl"
$RemotePort = "64321"
$RemotePath = "domains/ppm.mpptrade.pl/public_html"
$LocalPath = "D:\OneDrive - MPP TRADE\Skrypty\PPM-CC-Laravel"

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "Deploying Layout Elements (Column, Grid, Background)" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan

# 1. Deploy BlockBuilderCanvas.php (element definitions + methods)
Write-Host "`n[1/4] Deploying BlockBuilderCanvas.php..." -ForegroundColor Yellow
$phpDir = "app/Http/Livewire/Products/VisualDescription/BlockBuilder"
pscp -i $HostidoKey -P $RemotePort "$LocalPath/$phpDir/BlockBuilderCanvas.php" "${RemoteHost}:$RemotePath/$phpDir/BlockBuilderCanvas.php"

# 2. Deploy canvas.blade.php (element palette)
Write-Host "`n[2/4] Deploying canvas.blade.php with new palette buttons..." -ForegroundColor Yellow
$viewDir = "resources/views/livewire/products/visual-description/block-builder"
pscp -i $HostidoKey -P $RemotePort "$LocalPath/$viewDir/canvas.blade.php" "${RemoteHost}:$RemotePath/$viewDir/canvas.blade.php"

# 3. Deploy element-renderer.blade.php (rendering)
Write-Host "`n[3/4] Deploying element-renderer.blade.php..." -ForegroundColor Yellow
$partialsDir = "resources/views/livewire/products/visual-description/block-builder/partials"
pscp -i $HostidoKey -P $RemotePort "$LocalPath/$partialsDir/element-renderer.blade.php" "${RemoteHost}:$RemotePath/$partialsDir/element-renderer.blade.php"

# 4. Deploy property-panel.blade.php (controls)
Write-Host "`n[4/4] Deploying property-panel.blade.php with Grid/Background controls..." -ForegroundColor Yellow
pscp -i $HostidoKey -P $RemotePort "$LocalPath/$partialsDir/property-panel.blade.php" "${RemoteHost}:$RemotePath/$partialsDir/property-panel.blade.php"

# 5. Clear cache
Write-Host "`n[5/5] Clearing cache..." -ForegroundColor Yellow
plink -ssh $RemoteHost -P $RemotePort -i $HostidoKey -batch "cd $RemotePath && php artisan view:clear && php artisan cache:clear"

Write-Host "`n========================================" -ForegroundColor Green
Write-Host "Deployment completed!" -ForegroundColor Green
Write-Host "========================================" -ForegroundColor Green
Write-Host "`nNew elements added to Block Builder palette:" -ForegroundColor Cyan
Write-Host "  - Column (Kolumna) - vertical flex container" -ForegroundColor White
Write-Host "  - Grid (Siatka) - CSS Grid with column controls" -ForegroundColor White
Write-Host "  - Background (Tlo z obrazem) - section with background image + overlay" -ForegroundColor White
