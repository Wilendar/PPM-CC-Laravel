# Deploy Modal Centering Fix
# 2025-10-14 - UPDATED: Fix Tailwind flexbox conflict

$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
$LocalPath = "D:\OneDrive - MPP TRADE\Skrypty\PPM-CC-Laravel"
$RemotePath = "host379076@host379076.hostido.net.pl:domains/ppm.mpptrade.pl/public_html"

Write-Host "[1/2] Uploading fixed Blade template..." -ForegroundColor Cyan
pscp -i $HostidoKey -P 64321 "$LocalPath\resources\views\livewire\components\category-preview-modal.blade.php" "$RemotePath/resources/views/livewire/components/category-preview-modal.blade.php"

Write-Host "[2/2] Clearing Laravel view cache..." -ForegroundColor Cyan
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php artisan view:clear"

Write-Host "`nDeployment COMPLETE!" -ForegroundColor Green
Write-Host "Please refresh browser (Ctrl+Shift+R) to see changes" -ForegroundColor Yellow
