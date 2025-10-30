# Deploy Variants Page - PPM Color Compliance Fix
$ErrorActionPreference = "Stop"

$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
$RemoteBase = "host379076@host379076.hostido.net.pl:domains/ppm.mpptrade.pl/public_html"

Write-Host "`n=== DEPLOYING VARIANTS PAGE - PPM COLORS FIX ===" -ForegroundColor Cyan

# 1. Upload ALL assets
Write-Host "`n[1/4] Uploading ALL assets..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 -r "public/build/assets/*" "${RemoteBase}/public/build/assets/"
Write-Host "[OK] Assets uploaded" -ForegroundColor Green

# 2. Upload manifest to ROOT
Write-Host "`n[2/4] Uploading manifest to ROOT..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 "public/build/.vite/manifest.json" "${RemoteBase}/public/build/manifest.json"
Write-Host "[OK] Manifest uploaded" -ForegroundColor Green

# 3. Upload attribute-system-manager.blade.php
Write-Host "`n[3/4] Uploading attribute-system-manager.blade.php..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 "resources/views/livewire/admin/variants/attribute-system-manager.blade.php" "${RemoteBase}/resources/views/livewire/admin/variants/attribute-system-manager.blade.php"
Write-Host "[OK] Blade template uploaded" -ForegroundColor Green

# 4. Clear cache
Write-Host "`n[4/4] Clearing cache..." -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php artisan view:clear && php artisan cache:clear && php artisan config:clear"
Write-Host "[OK] Cache cleared" -ForegroundColor Green

Write-Host "`n=== DEPLOYMENT COMPLETE ===" -ForegroundColor Green
Write-Host "Fixed:" -ForegroundColor Yellow
Write-Host "  - Focus states: Blue -> MPP Orange (#e0ac7e)" -ForegroundColor White
Write-Host "  - Focus ring: Blue -> MPP Orange with 30% opacity" -ForegroundColor White
Write-Host "  - Card hover border: Blue -> MPP Orange" -ForegroundColor White
Write-Host "  - Checkbox accent: Blue -> MPP Orange" -ForegroundColor White
Write-Host "  - Values button: Blue -> MPP Orange gradient" -ForegroundColor White
Write-Host "  - Sync details link: Blue -> MPP Orange" -ForegroundColor White
