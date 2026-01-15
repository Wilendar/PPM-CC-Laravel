$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
$RemoteBase = "host379076@host379076.hostido.net.pl:domains/ppm.mpptrade.pl/public_html"

Write-Host "=== DEPLOYING GALLERY TAB FIX ===" -ForegroundColor Cyan

# 1. Upload active-operations-bar.blade.php (FIX dla RootTagMissing)
Write-Host "[1/4] Uploading fixed active-operations-bar.blade.php..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 "resources/views/livewire/components/active-operations-bar.blade.php" "${RemoteBase}/resources/views/livewire/components/active-operations-bar.blade.php"

# 2. Upload GalleryTab.php component
Write-Host "[2/4] Uploading GalleryTab.php..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 "app/Http/Livewire/Products/Management/Tabs/GalleryTab.php" "${RemoteBase}/app/Http/Livewire/Products/Management/Tabs/GalleryTab.php"

# 3. Upload gallery-tab.blade.php view
Write-Host "[3/4] Uploading gallery-tab.blade.php..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 "resources/views/livewire/products/management/tabs/gallery-tab.blade.php" "${RemoteBase}/resources/views/livewire/products/management/tabs/gallery-tab.blade.php"

# 4. Clear cache
Write-Host "[4/4] Clearing cache..." -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php artisan view:clear && php artisan cache:clear"

Write-Host "=== DEPLOYMENT COMPLETE ===" -ForegroundColor Green
