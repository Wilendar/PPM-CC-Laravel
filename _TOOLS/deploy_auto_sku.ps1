#!/usr/bin/env pwsh
# Deploy Auto SKU feature to Hostido
# ETAP_05f - Faza 3

$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
$RemoteBase = "host379076@host379076.hostido.net.pl:domains/ppm.mpptrade.pl/public_html"
$LocalBase = "D:\OneDrive - MPP TRADE\Skrypty\PPM-CC-Laravel"

Write-Host "=== DEPLOYING AUTO SKU FEATURE (ETAP_05f Faza 3) ===" -ForegroundColor Cyan

# 1. Upload VariantSkuGenerator.php (NEW SERVICE)
Write-Host "[1/6] Uploading VariantSkuGenerator.php..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 "$LocalBase\app\Services\Product\VariantSkuGenerator.php" "${RemoteBase}/app/Services/Product/VariantSkuGenerator.php"

# 2. Upload VariantCrudTrait.php
Write-Host "[2/6] Uploading VariantCrudTrait.php..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 "$LocalBase\app\Http\Livewire\Products\Management\Traits\VariantCrudTrait.php" "${RemoteBase}/app/Http/Livewire/Products/Management/Traits/VariantCrudTrait.php"

# 3. Upload VariantAttributeTrait.php
Write-Host "[3/6] Uploading VariantAttributeTrait.php..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 "$LocalBase\app\Http\Livewire\Products\Management\Traits\VariantAttributeTrait.php" "${RemoteBase}/app/Http/Livewire/Products/Management/Traits/VariantAttributeTrait.php"

# 4. Upload variant-create-modal.blade.php
Write-Host "[4/6] Uploading variant-create-modal.blade.php..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 "$LocalBase\resources\views\livewire\products\management\partials\variant-create-modal.blade.php" "${RemoteBase}/resources/views/livewire/products/management/partials/variant-create-modal.blade.php"

# 5. Upload variant-edit-modal.blade.php
Write-Host "[5/6] Uploading variant-edit-modal.blade.php..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 "$LocalBase\resources\views\livewire\products\management\partials\variant-edit-modal.blade.php" "${RemoteBase}/resources/views/livewire/products/management/partials/variant-edit-modal.blade.php"

# 6. Upload variants-tab.blade.php (MAIN modal file!)
Write-Host "[6/7] Uploading variants-tab.blade.php..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 "$LocalBase\resources\views\livewire\products\management\tabs\variants-tab.blade.php" "${RemoteBase}/resources/views/livewire/products/management/tabs/variants-tab.blade.php"

# 7. Clear Laravel cache
Write-Host "[6/6] Clearing Laravel cache..." -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php artisan view:clear && php artisan cache:clear && php artisan config:clear"

Write-Host "=== DEPLOYMENT COMPLETE ===" -ForegroundColor Green
Write-Host "Test at: https://ppm.mpptrade.pl/products/{id}/edit -> Warianty tab" -ForegroundColor Cyan
