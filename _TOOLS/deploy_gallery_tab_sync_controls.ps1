# Deploy Gallery Tab Sync Controls (PHASE 4.3)
# Date: 2025-12-01
# Changes:
# - GalleryTab.php: Job dispatch for pullFromShop + ActiveOperationsBar widget
# - gallery-tab.blade.php: Sync status icons + ActiveOperationsBar
# - media-gallery.css: Sync status icon styles

$ErrorActionPreference = "Stop"
$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
$LocalBase = "d:\OneDrive - MPP TRADE\Skrypty\PPM-CC-Laravel"
$RemoteBase = "domains/ppm.mpptrade.pl/public_html"
$RemoteHost = "host379076@host379076.hostido.net.pl"

Write-Host "`n=== DEPLOY GALLERY TAB SYNC CONTROLS ===" -ForegroundColor Cyan

# 1. Upload PHP file
Write-Host "`n[1/5] Uploading GalleryTab.php..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 `
    "$LocalBase\app\Http\Livewire\Products\Management\Tabs\GalleryTab.php" `
    "${RemoteHost}:${RemoteBase}/app/Http/Livewire/Products/Management/Tabs/"

# 2. Upload Blade file
Write-Host "`n[2/5] Uploading gallery-tab.blade.php..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 `
    "$LocalBase\resources\views\livewire\products\management\tabs\gallery-tab.blade.php" `
    "${RemoteHost}:${RemoteBase}/resources/views/livewire/products/management/tabs/"

# 3. Upload CSS assets (ALL - Vite regenerates all hashes)
Write-Host "`n[3/5] Uploading CSS assets..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 -r `
    "$LocalBase\public\build\assets\*.css" `
    "${RemoteHost}:${RemoteBase}/public/build/assets/"

# 4. Upload manifest to ROOT (CRITICAL!)
Write-Host "`n[4/5] Uploading Vite manifest..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 `
    "$LocalBase\public\build\.vite\manifest.json" `
    "${RemoteHost}:${RemoteBase}/public/build/manifest.json"

# 5. Clear cache
Write-Host "`n[5/5] Clearing Laravel cache..." -ForegroundColor Yellow
plink -ssh ${RemoteHost} -P 64321 -i $HostidoKey -batch `
    "cd ${RemoteBase} && php artisan view:clear && php artisan cache:clear && php artisan config:clear"

Write-Host "`n=== DEPLOYMENT COMPLETED ===" -ForegroundColor Green
Write-Host "`nVerify at: https://ppm.mpptrade.pl/admin/products" -ForegroundColor Cyan
