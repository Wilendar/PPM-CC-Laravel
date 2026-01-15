# Deploy wire:poll fix for JobProgressBar
$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
$RemoteBase = "host379076@host379076.hostido.net.pl:domains/ppm.mpptrade.pl/public_html"

Write-Host "=== DEPLOYING wire:poll FIX ===" -ForegroundColor Cyan

Write-Host "[1/2] Uploading job-progress-bar.blade.php..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 "resources/views/livewire/components/job-progress-bar.blade.php" "$RemoteBase/resources/views/livewire/components/job-progress-bar.blade.php"

Write-Host "[2/2] Clearing cache..." -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php artisan view:clear && php artisan cache:clear"

Write-Host "=== DEPLOYMENT COMPLETE ===" -ForegroundColor Green
