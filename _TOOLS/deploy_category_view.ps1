# Deploy Category View Improvements
$ErrorActionPreference = "Stop"

$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
$RemoteBase = "host379076@host379076.hostido.net.pl:domains/ppm.mpptrade.pl/public_html"

Write-Host "`n=== DEPLOYING CATEGORY VIEW IMPROVEMENTS ===" -ForegroundColor Cyan

# 1. Upload ALL assets
Write-Host "`n[1/4] Uploading ALL assets..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 -r "public/build/assets/*" "${RemoteBase}/public/build/assets/"
Write-Host "[OK] Assets uploaded" -ForegroundColor Green

# 2. Upload manifest to ROOT
Write-Host "`n[2/4] Uploading manifest to ROOT..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 "public/build/.vite/manifest.json" "${RemoteBase}/public/build/manifest.json"
Write-Host "[OK] Manifest uploaded" -ForegroundColor Green

# 3. Upload category-tree-ultra-clean.blade.php
Write-Host "`n[3/4] Uploading category-tree-ultra-clean.blade.php..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 "resources/views/livewire/products/categories/category-tree-ultra-clean.blade.php" "${RemoteBase}/resources/views/livewire/products/categories/category-tree-ultra-clean.blade.php"
Write-Host "[OK] Blade template uploaded" -ForegroundColor Green

# 4. Clear cache
Write-Host "`n[4/4] Clearing cache..." -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php artisan view:clear && php artisan cache:clear && php artisan config:clear"
Write-Host "[OK] Cache cleared" -ForegroundColor Green

Write-Host "`n=== DEPLOYMENT COMPLETE ===" -ForegroundColor Green
Write-Host "Fixed:" -ForegroundColor Yellow
Write-Host "  - Toggle Drzewo/Lista: PPM Orange border (jak shop tabs)" -ForegroundColor White
Write-Host "  - Search input: PPM Orange focus ring" -ForegroundColor White
Write-Host "  - Checkboxy: PPM Orange accent" -ForegroundColor White
Write-Host "  - Przyciski Rozwin/Zwin: PPM Orange hover" -ForegroundColor White
Write-Host "  - Przycisk Dodaj kategorie: PPM Orange gradient" -ForegroundColor White
