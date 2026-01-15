# Deploy ETAP_07c User Action State Fix
$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
$RemoteBase = "host379076@host379076.hostido.net.pl:domains/ppm.mpptrade.pl/public_html"

Write-Host "=== DEPLOYING ETAP_07c USER ACTION STATE FIX ===" -ForegroundColor Cyan

Write-Host "[1/4] Uploading CategoryPreviewModal.php (event dispatch)..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 "app/Http/Livewire/Components/CategoryPreviewModal.php" "$RemoteBase/app/Http/Livewire/Components/CategoryPreviewModal.php"

Write-Host "[2/4] Uploading JobProgressBar.php (listener + state)..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 "app/Http/Livewire/Components/JobProgressBar.php" "$RemoteBase/app/Http/Livewire/Components/JobProgressBar.php"

Write-Host "[3/4] Uploading job-progress-bar.blade.php (template)..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 "resources/views/livewire/components/job-progress-bar.blade.php" "$RemoteBase/resources/views/livewire/components/job-progress-bar.blade.php"

Write-Host "[4/4] Clearing cache..." -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php artisan view:clear && php artisan cache:clear && php artisan config:clear"

Write-Host "=== DEPLOYMENT COMPLETE ===" -ForegroundColor Green
