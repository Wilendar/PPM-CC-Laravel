# Deploy product-form.blade.php - Complete Light Mode Removal

$ErrorActionPreference = "Stop"

$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
$RemoteBase = "host379076@host379076.hostido.net.pl:domains/ppm.mpptrade.pl/public_html"

Write-Host "`n=== DEPLOYING PRODUCT-FORM.BLADE.PHP ===" -ForegroundColor Cyan

# Deploy product-form.blade.php
Write-Host "`n[1/2] Deploying product-form.blade.php..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 "resources/views/livewire/products/management/product-form.blade.php" "${RemoteBase}/resources/views/livewire/products/management/product-form.blade.php"
Write-Host "[OK] product-form.blade.php deployed" -ForegroundColor Green

# Clear cache
Write-Host "`n[2/2] Clearing Laravel cache..." -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch `
  "cd domains/ppm.mpptrade.pl/public_html && php artisan view:clear && php artisan cache:clear"
Write-Host "[OK] Cache cleared" -ForegroundColor Green

Write-Host "`n=== DEPLOYMENT COMPLETE ===" -ForegroundColor Green
Write-Host "Fixed light mode fallbacks:" -ForegroundColor Yellow
Write-Host "  - All bg-gray-50/100 removed" -ForegroundColor White
Write-Host "  - All text-gray-600 removed" -ForegroundColor White
Write-Host "  - sort_order now uses getFieldClasses()" -ForegroundColor White
