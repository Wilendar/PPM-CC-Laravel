$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"

Write-Host "=== UPLOADING VARIANT ARRAYS FIX ===" -ForegroundColor Cyan
Write-Host "Fix: Initialize variantPrices and variantStock for new variants" -ForegroundColor Yellow
Write-Host ""

Write-Host "Uploading ProductFormVariants.php..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 `
  "app/Http/Livewire/Products/Management/Traits/ProductFormVariants.php" `
  "host379076@host379076.hostido.net.pl:domains/ppm.mpptrade.pl/public_html/app/Http/Livewire/Products/Management/Traits/ProductFormVariants.php"

if ($LASTEXITCODE -eq 0) {
    Write-Host ""
    Write-Host "Clearing cache..." -ForegroundColor Cyan
    plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch `
      "cd domains/ppm.mpptrade.pl/public_html && php artisan view:clear && php artisan cache:clear"

    Write-Host ""
    Write-Host "=== UPLOAD COMPLETE ===" -ForegroundColor Green
    Write-Host "Test: Add a new variant and check browser console" -ForegroundColor Cyan
} else {
    Write-Host ""
    Write-Host "=== UPLOAD FAILED ===" -ForegroundColor Red
}
