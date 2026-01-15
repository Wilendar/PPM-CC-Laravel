# FAZA 6.5 Deployment Script
# Resizable columns + Marka/Cena columns + DescriptionModal

$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
$RemoteBase = "host379076@host379076.hostido.net.pl:domains/ppm.mpptrade.pl/public_html"

Write-Host "=== DEPLOYING FAZA 6.5 ===" -ForegroundColor Cyan

# 1. Upload ALL assets
Write-Host "[1/9] Uploading assets..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 -r "public/build/assets/*" "$RemoteBase/public/build/assets/"

# 2. Upload manifest to ROOT
Write-Host "[2/9] Uploading manifest..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 "public/build/.vite/manifest.json" "$RemoteBase/public/build/manifest.json"

# 3. Upload resizable-columns.js (source)
Write-Host "[3/9] Uploading resizable-columns.js..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 "resources/js/resizable-columns.js" "$RemoteBase/resources/js/resizable-columns.js"

# 4. Upload app.js (source)
Write-Host "[4/9] Uploading app.js..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 "resources/js/app.js" "$RemoteBase/resources/js/app.js"

# 5. Upload DescriptionModal PHP
Write-Host "[5/9] Uploading DescriptionModal.php..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 "app/Http/Livewire/Products/Import/Modals/DescriptionModal.php" "$RemoteBase/app/Http/Livewire/Products/Import/Modals/DescriptionModal.php"

# 6. Upload DescriptionModal blade
Write-Host "[6/9] Uploading description-modal.blade.php..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 "resources/views/livewire/products/import/modals/description-modal.blade.php" "$RemoteBase/resources/views/livewire/products/import/modals/description-modal.blade.php"

# 7. Upload PendingProduct model
Write-Host "[7/9] Uploading PendingProduct.php..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 "app/Models/PendingProduct.php" "$RemoteBase/app/Models/PendingProduct.php"

# 8. Upload product-import-panel.blade.php
Write-Host "[8/9] Uploading product-import-panel.blade.php..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 "resources/views/livewire/products/import/product-import-panel.blade.php" "$RemoteBase/resources/views/livewire/products/import/product-import-panel.blade.php"

# 9. Upload product-row.blade.php
Write-Host "[9/9] Uploading product-row.blade.php..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 "resources/views/livewire/products/import/partials/product-row.blade.php" "$RemoteBase/resources/views/livewire/products/import/partials/product-row.blade.php"

Write-Host "=== FILES UPLOADED ===" -ForegroundColor Green

# 10. Run migration
Write-Host "[10/11] Running migration..." -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php artisan migrate --force"

# 11. Clear cache
Write-Host "[11/11] Clearing cache..." -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php artisan view:clear && php artisan cache:clear && php artisan config:clear"

Write-Host "=== FAZA 6.5 DEPLOYMENT COMPLETE ===" -ForegroundColor Green
