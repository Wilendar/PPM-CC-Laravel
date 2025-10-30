# Deploy ProductForm.php Dark Input Fields Fix

$ErrorActionPreference = "Stop"

$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
$RemoteBase = "host379076@host379076.hostido.net.pl:domains/ppm.mpptrade.pl/public_html"

Write-Host "`n=== DEPLOYING PRODUCTFORM.PHP FIX ===" -ForegroundColor Cyan

# Deploy ProductForm.php
Write-Host "`n[1/2] Deploying ProductForm.php..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 "app/Http/Livewire/Products/Management/ProductForm.php" "${RemoteBase}/app/Http/Livewire/Products/Management/ProductForm.php"
Write-Host "[OK] ProductForm.php deployed" -ForegroundColor Green

# Clear cache
Write-Host "`n[2/2] Clearing Laravel cache..." -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch `
  "cd domains/ppm.mpptrade.pl/public_html && php artisan view:clear && php artisan cache:clear && php artisan config:clear"
Write-Host "[OK] Cache cleared" -ForegroundColor Green

Write-Host "`n=== DEPLOYMENT COMPLETE ===" -ForegroundColor Green
Write-Host "All input fields should now have dark backgrounds!" -ForegroundColor Yellow
