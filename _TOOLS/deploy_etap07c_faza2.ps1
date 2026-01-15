# ETAP_07c FAZA 2 Deployment - JobProgressBar Accordion Enhancement
# Date: 2025-11-28

$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
$RemoteBase = "host379076@host379076.hostido.net.pl:domains/ppm.mpptrade.pl/public_html"
$SSHHost = "host379076@host379076.hostido.net.pl"

Write-Host "=== ETAP_07c FAZA 2 DEPLOYMENT ===" -ForegroundColor Cyan
Write-Host "JobProgressBar Accordion Enhancement" -ForegroundColor Yellow

# 1. Upload Livewire Component
Write-Host "`n[1/7] Uploading JobProgressBar.php..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 "app/Http/Livewire/Components/JobProgressBar.php" "${RemoteBase}/app/Http/Livewire/Components/JobProgressBar.php"

# 2. Upload Service
Write-Host "[2/7] Uploading JobProgressService.php..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 "app/Services/JobProgressService.php" "${RemoteBase}/app/Services/JobProgressService.php"

# 3. Upload Main Blade Template
Write-Host "[3/7] Uploading job-progress-bar.blade.php..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 "resources/views/livewire/components/job-progress-bar.blade.php" "${RemoteBase}/resources/views/livewire/components/job-progress-bar.blade.php"

# 4. Create partials directory and upload icon partial
Write-Host "[4/7] Creating partials directory and uploading job-progress-icon.blade.php..." -ForegroundColor Yellow
plink -ssh $SSHHost -P 64321 -i $HostidoKey -batch "mkdir -p domains/ppm.mpptrade.pl/public_html/resources/views/livewire/components/partials"
pscp -i $HostidoKey -P 64321 "resources/views/livewire/components/partials/job-progress-icon.blade.php" "${RemoteBase}/resources/views/livewire/components/partials/job-progress-icon.blade.php"

# 5. Upload ALL assets (Vite regenerates all hashes)
Write-Host "[5/7] Uploading ALL assets..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 -r "public/build/assets/*" "${RemoteBase}/public/build/assets/"

# 6. Upload manifest to ROOT (MANDATORY - Laravel needs it there)
Write-Host "[6/7] Uploading manifest to ROOT..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 "public/build/.vite/manifest.json" "${RemoteBase}/public/build/manifest.json"

# 7. Clear cache
Write-Host "[7/7] Clearing cache..." -ForegroundColor Yellow
plink -ssh $SSHHost -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php artisan view:clear && php artisan cache:clear && php artisan config:clear"

Write-Host "`n=== DEPLOYMENT COMPLETE ===" -ForegroundColor Green
Write-Host "Test: Start import from https://ppm.mpptrade.pl/admin/products" -ForegroundColor Cyan
Write-Host "Expand accordion to see rich details" -ForegroundColor Cyan
