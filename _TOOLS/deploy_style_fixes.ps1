# Deploy Product Form Style Fixes
$ErrorActionPreference = "Stop"

$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
$RemoteBase = "host379076@host379076.hostido.net.pl:domains/ppm.mpptrade.pl/public_html"

Write-Host "`n=== DEPLOYING STYLE FIXES ===" -ForegroundColor Cyan

# 1. Upload ALL assets
Write-Host "`n[1/6] Uploading ALL assets..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 -r "public/build/assets/*" "${RemoteBase}/public/build/assets/"
Write-Host "[OK] Assets uploaded" -ForegroundColor Green

# 2. Upload manifest to ROOT
Write-Host "`n[2/6] Uploading manifest to ROOT..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 "public/build/.vite/manifest.json" "${RemoteBase}/public/build/manifest.json"
Write-Host "[OK] Manifest uploaded" -ForegroundColor Green

# 3. Upload ProductForm.php
Write-Host "`n[3/6] Uploading ProductForm.php..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 "app/Http/Livewire/Products/Management/ProductForm.php" "${RemoteBase}/app/Http/Livewire/Products/Management/ProductForm.php"
Write-Host "[OK] ProductForm.php uploaded" -ForegroundColor Green

# 4. Upload category-tree-item.blade.php
Write-Host "`n[4/6] Uploading category-tree-item.blade.php..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 "resources/views/livewire/products/management/partials/category-tree-item.blade.php" "${RemoteBase}/resources/views/livewire/products/management/partials/category-tree-item.blade.php"
Write-Host "[OK] category-tree-item uploaded" -ForegroundColor Green

# 5. Upload product-form.blade.php
Write-Host "`n[5/6] Uploading product-form.blade.php..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 "resources/views/livewire/products/management/product-form.blade.php" "${RemoteBase}/resources/views/livewire/products/management/product-form.blade.php"
Write-Host "[OK] product-form uploaded" -ForegroundColor Green

# 6. Clear cache
Write-Host "`n[6/6] Clearing cache..." -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php artisan view:clear && php artisan cache:clear && php artisan config:clear"
Write-Host "[OK] Cache cleared" -ForegroundColor Green

Write-Host "`n=== DEPLOYMENT COMPLETE ===" -ForegroundColor Green
Write-Host "Fixed:" -ForegroundColor Yellow
Write-Host "  - Category tree font color (white on dark)" -ForegroundColor White
Write-Host "  - 'Dane domyslne' button (warm orange gradient)" -ForegroundColor White
Write-Host "  - 'Aktywne' badges (subtle orange)" -ForegroundColor White
Write-Host "  - Colored input fields (delicate borders + tinted bg)" -ForegroundColor White
Write-Host "  - Status labels (intensified colors)" -ForegroundColor White
Write-Host "  - 'Dziedziczone' purple tint added" -ForegroundColor White
