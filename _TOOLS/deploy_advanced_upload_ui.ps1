# Deploy Advanced Upload UI (ETAP_07d Phase 3)
# Date: 2025-12-01
# Livewire Specialist

$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
$RemoteHost = "host379076@host379076.hostido.net.pl"
$RemotePath = "domains/ppm.mpptrade.pl/public_html"

Write-Host "=== DEPLOYMENT: Advanced Upload UI (ETAP_07d Phase 3) ===" -ForegroundColor Cyan

# 1. Upload PHP component
Write-Host "1. Uploading GalleryTab.php..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 "app\Http\Livewire\Products\Management\Tabs\GalleryTab.php" "${RemoteHost}:${RemotePath}/app/Http/Livewire/Products/Management/Tabs/"

# 2. Upload Blade view
Write-Host "2. Uploading gallery-tab.blade.php..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 "resources\views\livewire\products\management\tabs\gallery-tab.blade.php" "${RemoteHost}:${RemotePath}/resources/views/livewire/products/management/tabs/"

# 3. Upload ALL assets (Vite regenerates ALL hashes!)
Write-Host "3. Uploading ALL assets..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 -r "public\build\assets\*" "${RemoteHost}:${RemotePath}/public/build/assets/"

# 4. Upload manifest to ROOT (CRITICAL!)
Write-Host "4. Uploading manifest to ROOT..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 "public\build\.vite\manifest.json" "${RemoteHost}:${RemotePath}/public/build/manifest.json"

# 5. Clear cache
Write-Host "5. Clearing Laravel cache..." -ForegroundColor Yellow
plink -ssh $RemoteHost -P 64321 -i $HostidoKey -batch "cd $RemotePath && php artisan view:clear && php artisan cache:clear && php artisan config:clear"

Write-Host "=== DEPLOYMENT COMPLETED ===" -ForegroundColor Green
