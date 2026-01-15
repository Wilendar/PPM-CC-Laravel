$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"

Write-Host "=== DEPLOYING PRESTASHOP8CLIENT FIX ===" -ForegroundColor Cyan
Write-Host ""

Write-Host "[1/2] Uploading PrestaShop8Client.php..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 `
  "app/Services/PrestaShop/PrestaShop8Client.php" `
  "host379076@host379076.hostido.net.pl:domains/ppm.mpptrade.pl/public_html/app/Services/PrestaShop/PrestaShop8Client.php"

if ($LASTEXITCODE -eq 0) {
    Write-Host "[OK] PrestaShop8Client.php uploaded" -ForegroundColor Green
} else {
    Write-Host "[ERROR] Upload failed!" -ForegroundColor Red
    exit 1
}

Write-Host ""
Write-Host "[2/3] Clearing Laravel cache and failed jobs..." -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch `
  "cd domains/ppm.mpptrade.pl/public_html && php artisan cache:clear && php artisan queue:flush"

if ($LASTEXITCODE -eq 0) {
    Write-Host "[OK] Cache cleared" -ForegroundColor Green
} else {
    Write-Host "[WARNING] Cache clear failed (not critical)" -ForegroundColor Yellow
}

Write-Host ""
Write-Host "[3/3] Verifying getProductImages method..." -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch `
  "grep -n 'function getProductImages' domains/ppm.mpptrade.pl/public_html/app/Services/PrestaShop/PrestaShop8Client.php"

Write-Host ""
Write-Host "=== DEPLOYMENT COMPLETE ===" -ForegroundColor Green
Write-Host "getProductImages() method is now available in PrestaShop8Client!" -ForegroundColor Cyan
