$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
$RemoteBase = "host379076@host379076.hostido.net.pl:domains/ppm.mpptrade.pl/public_html/resources/views/livewire/products/management/partials"

Write-Host "=== DEPLOYING VARIANT ORPHAN MODAL ===" -ForegroundColor Cyan
Write-Host ""

Write-Host "[1/2] Deploying variant-orphan-modal.blade.php..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 `
  "resources/views/livewire/products/management/partials/variant-orphan-modal.blade.php" `
  "$RemoteBase/variant-orphan-modal.blade.php"

if ($LASTEXITCODE -eq 0) {
    Write-Host "[OK] Modal deployed" -ForegroundColor Green
} else {
    Write-Host "[ERROR] Modal deployment failed!" -ForegroundColor Red
    exit 1
}

Write-Host ""
Write-Host "[2/2] Clearing Laravel cache..." -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch `
  "cd domains/ppm.mpptrade.pl/public_html && php artisan view:clear && php artisan cache:clear"

if ($LASTEXITCODE -eq 0) {
    Write-Host "[OK] Cache cleared" -ForegroundColor Green
} else {
    Write-Host "[ERROR] Cache clear failed!" -ForegroundColor Red
}

Write-Host ""
Write-Host "=== DEPLOYMENT COMPLETE ===" -ForegroundColor Green
