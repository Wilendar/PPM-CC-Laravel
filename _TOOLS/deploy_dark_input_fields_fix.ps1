# Deploy Dark Input Fields Fix to Production
# Global dark theme styles for all form elements

$ErrorActionPreference = "Stop"
[Console]::OutputEncoding = [System.Text.Encoding]::UTF8

$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
$RemoteBase = "host379076@host379076.hostido.net.pl:domains/ppm.mpptrade.pl/public_html"

Write-Host "`n=== DEPLOYING DARK INPUT FIELDS FIX ===" -ForegroundColor Cyan

# 1. Deploy ALL assets (Vite content-based hashing = ALL files get new hashes)
Write-Host "`n[1/3] Deploying ALL assets from public/build/assets/..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 -r "public/build/assets" "${RemoteBase}/public/build/"
Write-Host "[OK] All assets deployed" -ForegroundColor Green

# 2. Deploy manifest.json to ROOT location (CRITICAL!)
Write-Host "`n[2/3] Deploying manifest.json to ROOT..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 "public/build/.vite/manifest.json" "${RemoteBase}/public/build/manifest.json"
Write-Host "[OK] Manifest deployed to ROOT" -ForegroundColor Green

# 3. Clear Laravel cache
Write-Host "`n[3/3] Clearing Laravel cache..." -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch `
  "cd domains/ppm.mpptrade.pl/public_html && php artisan view:clear && php artisan cache:clear && php artisan config:clear"
Write-Host "[OK] Cache cleared" -ForegroundColor Green

Write-Host "`n=== DEPLOYMENT COMPLETE ===" -ForegroundColor Green
Write-Host "Dark input fields fix deployed successfully!" -ForegroundColor Yellow
Write-Host "New CSS files:" -ForegroundColor Cyan
Write-Host "  - app-Ci26E1Pj.css (with dark form elements)" -ForegroundColor White
Write-Host "  - components-9sEVYbxA.css" -ForegroundColor White
Write-Host "  - layout-CBQLZIVc.css" -ForegroundColor White
