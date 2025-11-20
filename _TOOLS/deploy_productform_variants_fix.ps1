$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
$RemoteBase = "host379076@host379076.hostido.net.pl:domains/ppm.mpptrade.pl/public_html/app/Http/Livewire/Products/Management/Traits"

Write-Host "=== DEPLOYING MODAL CLOSE FIX ===" -ForegroundColor Cyan
Write-Host ""

Write-Host "[1/2] Uploading ProductFormVariants.php..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 `
  "app/Http/Livewire/Products/Management/Traits/ProductFormVariants.php" `
  "$RemoteBase/ProductFormVariants.php"

if ($LASTEXITCODE -eq 0) {
    Write-Host "[OK] Trait deployed" -ForegroundColor Green
} else {
    Write-Host "[ERROR] Deployment failed!" -ForegroundColor Red
    exit 1
}

Write-Host ""
Write-Host "[2/2] Clearing Laravel cache..." -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch `
  "cd domains/ppm.mpptrade.pl/public_html && php artisan cache:clear && php artisan view:clear"

if ($LASTEXITCODE -eq 0) {
    Write-Host "[OK] Cache cleared" -ForegroundColor Green
} else {
    Write-Host "[ERROR] Cache clear failed!" -ForegroundColor Red
}

Write-Host ""
Write-Host "=== DEPLOYMENT COMPLETE ===" -ForegroundColor Green
Write-Host "Modal will now close properly after creating/editing variants!" -ForegroundColor Cyan
