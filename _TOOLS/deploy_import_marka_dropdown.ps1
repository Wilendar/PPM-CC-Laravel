# Deploy Import Panel Marka Dropdown Fix
$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
$RemoteBase = "host379076@host379076.hostido.net.pl:domains/ppm.mpptrade.pl/public_html"

Write-Host "=== DEPLOYING IMPORT PANEL MARKA DROPDOWN FIX ===" -ForegroundColor Cyan

# 1. Upload ProductImportPanel.php
Write-Host "[1/5] Uploading ProductImportPanel.php..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 "app/Http/Livewire/Products/Import/ProductImportPanel.php" "$RemoteBase/app/Http/Livewire/Products/Import/ProductImportPanel.php"

# 2. Upload ImportPanelActions.php
Write-Host "[2/5] Uploading ImportPanelActions.php..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 "app/Http/Livewire/Products/Import/Traits/ImportPanelActions.php" "$RemoteBase/app/Http/Livewire/Products/Import/Traits/ImportPanelActions.php"

# 3. Upload PendingProduct.php
Write-Host "[3/5] Uploading PendingProduct.php..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 "app/Models/PendingProduct.php" "$RemoteBase/app/Models/PendingProduct.php"

# 4. Upload product-row.blade.php
Write-Host "[4/5] Uploading product-row.blade.php..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 "resources/views/livewire/products/import/partials/product-row.blade.php" "$RemoteBase/resources/views/livewire/products/import/partials/product-row.blade.php"

# 5. Clear cache
Write-Host "[5/5] Clearing cache..." -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php artisan view:clear && php artisan cache:clear && php artisan config:clear"

Write-Host "=== DEPLOYMENT COMPLETE ===" -ForegroundColor Green
Write-Host "Test: https://ppm.mpptrade.pl/admin/import" -ForegroundColor Cyan
