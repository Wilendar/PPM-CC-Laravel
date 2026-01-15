# Deploy ETAP_07c FAZA 4 Full Fix
$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
$RemoteBase = "host379076@host379076.hostido.net.pl:domains/ppm.mpptrade.pl/public_html"

Write-Host "=== DEPLOYING ETAP_07c FAZA 4 FULL FIX ===" -ForegroundColor Cyan

Write-Host "[1/7] Uploading JobProgress.php (scopeActive fix)..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 "app/Models/JobProgress.php" "$RemoteBase/app/Models/JobProgress.php"

Write-Host "[2/7] Uploading JobProgressBar.php (event dispatch + message fix)..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 "app/Http/Livewire/Components/JobProgressBar.php" "$RemoteBase/app/Http/Livewire/Components/JobProgressBar.php"

Write-Host "[3/7] Uploading ProductList.php (removed auto-open)..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 "app/Http/Livewire/Products/Listing/ProductList.php" "$RemoteBase/app/Http/Livewire/Products/Listing/ProductList.php"

Write-Host "[4/7] Uploading job-progress-bar.blade.php (wire:poll + CSS classes)..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 "resources/views/livewire/components/job-progress-bar.blade.php" "$RemoteBase/resources/views/livewire/components/job-progress-bar.blade.php"

Write-Host "[5/7] Uploading ALL compiled assets..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 -r "public/build/assets/*" "$RemoteBase/public/build/assets/"

Write-Host "[6/7] Uploading manifest to ROOT..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 "public/build/.vite/manifest.json" "$RemoteBase/public/build/manifest.json"

Write-Host "[7/7] Clearing cache..." -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php artisan view:clear && php artisan cache:clear && php artisan config:clear"

Write-Host "=== DEPLOYMENT COMPLETE ===" -ForegroundColor Green
