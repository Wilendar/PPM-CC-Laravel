$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
$RemoteBase = "host379076@host379076.hostido.net.pl:domains/ppm.mpptrade.pl/public_html"

Write-Host "=== DEPLOYING VARIANT IMPORT FIX ===" -ForegroundColor Cyan

# 1. Upload ProductList.php
Write-Host "[1/6] Uploading ProductList.php..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 "app/Http/Livewire/Products/Listing/ProductList.php" "${RemoteBase}/app/Http/Livewire/Products/Listing/ProductList.php"

# 2. Upload product-list.blade.php
Write-Host "[2/6] Uploading product-list.blade.php..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 "resources/views/livewire/products/listing/product-list.blade.php" "${RemoteBase}/resources/views/livewire/products/listing/product-list.blade.php"

# 3. Upload PrestaShopImportService.php
Write-Host "[3/6] Uploading PrestaShopImportService.php..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 "app/Services/PrestaShop/PrestaShopImportService.php" "${RemoteBase}/app/Services/PrestaShop/PrestaShopImportService.php"

# 4. Upload BulkImportProducts.php
Write-Host "[4/6] Uploading BulkImportProducts.php..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 "app/Jobs/PrestaShop/BulkImportProducts.php" "${RemoteBase}/app/Jobs/PrestaShop/BulkImportProducts.php"

# 5. Upload Vite assets
Write-Host "[5/6] Uploading Vite assets..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 -r "public/build/assets/*" "${RemoteBase}/public/build/assets/"
pscp -i $HostidoKey -P 64321 "public/build/.vite/manifest.json" "${RemoteBase}/public/build/manifest.json"

# 6. Clear cache
Write-Host "[6/6] Clearing cache..." -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php artisan view:clear && php artisan cache:clear && php artisan config:clear && php artisan route:clear"

Write-Host "=== DEPLOYMENT COMPLETE ===" -ForegroundColor Green
