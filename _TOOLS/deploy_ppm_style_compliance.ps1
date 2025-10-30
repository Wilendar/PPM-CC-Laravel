# Deploy PPM Style Compliance - Category Tree + Buttons + Labels
$ErrorActionPreference = "Stop"

$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
$RemoteBase = "host379076@host379076.hostido.net.pl:domains/ppm.mpptrade.pl/public_html"

Write-Host "`n=== DEPLOYING PPM STYLE COMPLIANCE ===" -ForegroundColor Cyan

# 1. Upload ALL assets
Write-Host "`n[1/5] Uploading ALL assets..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 -r "public/build/assets/*" "${RemoteBase}/public/build/assets/"
Write-Host "[OK] Assets uploaded" -ForegroundColor Green

# 2. Upload manifest to ROOT
Write-Host "`n[2/5] Uploading manifest to ROOT..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 "public/build/.vite/manifest.json" "${RemoteBase}/public/build/manifest.json"
Write-Host "[OK] Manifest uploaded" -ForegroundColor Green

# 3. Upload category-tree-item.blade.php
Write-Host "`n[3/5] Uploading category-tree-item.blade.php..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 "resources/views/livewire/products/management/partials/category-tree-item.blade.php" "${RemoteBase}/resources/views/livewire/products/management/partials/category-tree-item.blade.php"
Write-Host "[OK] category-tree-item uploaded" -ForegroundColor Green

# 4. Upload product-form.blade.php
Write-Host "`n[4/5] Uploading product-form.blade.php..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 "resources/views/livewire/products/management/product-form.blade.php" "${RemoteBase}/resources/views/livewire/products/management/product-form.blade.php"
Write-Host "[OK] product-form uploaded" -ForegroundColor Green

# 5. Clear cache
Write-Host "`n[5/5] Clearing cache..." -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php artisan view:clear && php artisan cache:clear && php artisan config:clear"
Write-Host "[OK] Cache cleared" -ForegroundColor Green

Write-Host "`n=== DEPLOYMENT COMPLETE ===" -ForegroundColor Green
Write-Host "Fixed:" -ForegroundColor Yellow
Write-Host "  - Rounded corners na kolorowych statusach" -ForegroundColor White
Write-Host "  - Category tree: wieksza czcionka + lepszy kontrast" -ForegroundColor White
Write-Host "  - Labele 'Glowna'/'Ustaw glowna': PPM Blue + Gray" -ForegroundColor White
Write-Host "  - Label 'Wyrozniony': PPM Amber" -ForegroundColor White
Write-Host "  - Przycisk 'Przywroc domyslne': PPM Amber gradient" -ForegroundColor White
