# Deploy WYSIWYG Editor - ETAP_07f_P4
# Deploys rich text editing feature for Block Builder

$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
$RemoteHost = "host379076@host379076.hostido.net.pl"
$RemotePort = "64321"
$RemotePath = "domains/ppm.mpptrade.pl/public_html"
$LocalPath = "D:\OneDrive - MPP TRADE\Skrypty\PPM-CC-Laravel"

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "Deploying WYSIWYG Editor Feature" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan

# 1. Deploy canvas.blade.php (contains wysiwygEditor Alpine component)
Write-Host "`n[1/3] Deploying canvas.blade.php with WYSIWYG JS..." -ForegroundColor Yellow
$viewDir = "resources/views/livewire/products/visual-description/block-builder"
pscp -i $HostidoKey -P $RemotePort "$LocalPath/$viewDir/canvas.blade.php" "${RemoteHost}:$RemotePath/$viewDir/canvas.blade.php"

# 2. Deploy property-panel.blade.php (contains WYSIWYG toolbar)
Write-Host "`n[2/3] Deploying property-panel.blade.php with WYSIWYG toolbar..." -ForegroundColor Yellow
$partialsDir = "resources/views/livewire/products/visual-description/block-builder/partials"
pscp -i $HostidoKey -P $RemotePort "$LocalPath/$partialsDir/property-panel.blade.php" "${RemoteHost}:$RemotePath/$partialsDir/property-panel.blade.php"

# 3. Deploy element-renderer.blade.php (renders HTML content)
Write-Host "`n[3/3] Deploying element-renderer.blade.php..." -ForegroundColor Yellow
pscp -i $HostidoKey -P $RemotePort "$LocalPath/$partialsDir/element-renderer.blade.php" "${RemoteHost}:$RemotePath/$partialsDir/element-renderer.blade.php"

# 4. Clear cache
Write-Host "`n[4/4] Clearing cache..." -ForegroundColor Yellow
plink -ssh $RemoteHost -P $RemotePort -i $HostidoKey -batch "cd $RemotePath && php artisan view:clear && php artisan cache:clear"

Write-Host "`n========================================" -ForegroundColor Green
Write-Host "Deployment completed!" -ForegroundColor Green
Write-Host "========================================" -ForegroundColor Green
Write-Host "`nTest: Open Block Builder, add Text element, and use formatting toolbar" -ForegroundColor Cyan
Write-Host "Available formats: Bold (Ctrl+B), Italic (Ctrl+I), Underline (Ctrl+U), Link (Ctrl+K)" -ForegroundColor Cyan
