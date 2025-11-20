$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"

Write-Host "=== CREATING NEW TEST PRODUCT WITH VARIANTS ===" -ForegroundColor Cyan
Write-Host ""

Write-Host "[1/2] Uploading creation script..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 `
  "_TEMP/create_test_product_with_variants.php" `
  "host379076@host379076.hostido.net.pl:domains/ppm.mpptrade.pl/public_html/_TEMP/create_test_product_with_variants.php"

if ($LASTEXITCODE -eq 0) {
    Write-Host ""
    Write-Host "[2/2] Running script on production..." -ForegroundColor Yellow
    plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch `
      "cd domains/ppm.mpptrade.pl/public_html && php _TEMP/create_test_product_with_variants.php"

    Write-Host ""
    Write-Host "=== TEST PRODUCT CREATED ===" -ForegroundColor Green
} else {
    Write-Host ""
    Write-Host "=== UPLOAD FAILED ===" -ForegroundColor Red
}
