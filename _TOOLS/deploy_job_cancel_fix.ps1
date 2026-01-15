# Deploy Job Cancel Fix
# FIX (2025-12-10): Przycisk X anuluje job zamiast tylko ukrywac

$ErrorActionPreference = "Stop"
$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
$RemoteBase = "host379076@host379076.hostido.net.pl:domains/ppm.mpptrade.pl/public_html"

Write-Host "=== DEPLOYING JOB CANCEL FIX ===" -ForegroundColor Cyan

Write-Host "[1/4] Uploading JobProgress.php..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 "app/Models/JobProgress.php" "${RemoteBase}/app/Models/JobProgress.php"

Write-Host "[2/4] Uploading JobProgressBar.php..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 "app/Http/Livewire/Components/JobProgressBar.php" "${RemoteBase}/app/Http/Livewire/Components/JobProgressBar.php"

Write-Host "[3/4] Uploading job-progress-bar.blade.php..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 "resources/views/livewire/components/job-progress-bar.blade.php" "${RemoteBase}/resources/views/livewire/components/job-progress-bar.blade.php"

Write-Host "[4/4] Clearing cache..." -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php artisan view:clear && php artisan cache:clear && php artisan config:clear && php artisan optimize:clear"

Write-Host "=== DEPLOYMENT COMPLETE ===" -ForegroundColor Green
