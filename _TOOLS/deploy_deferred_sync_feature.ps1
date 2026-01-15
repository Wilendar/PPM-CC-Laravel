$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
$RemoteBase = "host379076@host379076.hostido.net.pl:domains/ppm.mpptrade.pl/public_html"

Write-Host "=== DEPLOYING DEFERRED SYNC FEATURE ===" -ForegroundColor Cyan

Write-Host "[1/6] Uploading ALL assets..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 -r "public/build/assets/*" "$RemoteBase/public/build/assets/"

Write-Host "[2/6] Uploading manifest to ROOT..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 "public/build/.vite/manifest.json" "$RemoteBase/public/build/manifest.json"

Write-Host "[3/6] Uploading GalleryTab.php..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 "app/Http/Livewire/Products/Management/Tabs/GalleryTab.php" "$RemoteBase/app/Http/Livewire/Products/Management/Tabs/GalleryTab.php"

Write-Host "[4/6] Uploading gallery-tab.blade.php..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 "resources/views/livewire/products/management/tabs/gallery-tab.blade.php" "$RemoteBase/resources/views/livewire/products/management/tabs/gallery-tab.blade.php"

Write-Host "[5/6] Uploading media-gallery.css..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 "resources/css/products/media-gallery.css" "$RemoteBase/resources/css/products/media-gallery.css"

Write-Host "[6/6] Clearing cache..." -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php artisan view:clear && php artisan cache:clear && php artisan config:clear"

Write-Host "=== DEPLOYMENT COMPLETE ===" -ForegroundColor Green
