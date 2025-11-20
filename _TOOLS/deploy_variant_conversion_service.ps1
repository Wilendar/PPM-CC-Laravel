$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
$RemoteBase = "host379076@host379076.hostido.net.pl:domains/ppm.mpptrade.pl/public_html/app/Services"

Write-Host "=== DEPLOYING VARIANT CONVERSION SERVICE ===" -ForegroundColor Cyan
Write-Host ""

Write-Host "[1/2] Deploying VariantConversionService.php..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 `
  "app/Services/VariantConversionService.php" `
  "$RemoteBase/VariantConversionService.php"

if ($LASTEXITCODE -eq 0) {
    Write-Host "[OK] Service deployed" -ForegroundColor Green
} else {
    Write-Host "[ERROR] Service deployment failed!" -ForegroundColor Red
    exit 1
}

Write-Host ""
Write-Host "[2/2] Clearing Laravel cache..." -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch `
  "cd domains/ppm.mpptrade.pl/public_html && php artisan cache:clear && php artisan config:clear"

if ($LASTEXITCODE -eq 0) {
    Write-Host "[OK] Cache cleared" -ForegroundColor Green
} else {
    Write-Host "[ERROR] Cache clear failed!" -ForegroundColor Red
}

Write-Host ""
Write-Host "=== DEPLOYMENT COMPLETE ===" -ForegroundColor Green
