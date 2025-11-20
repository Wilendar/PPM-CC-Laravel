$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"

Write-Host "=== UPLOADING CHECK SCRIPT ===" -ForegroundColor Cyan
pscp -i $HostidoKey -P 64321 `
  "_TEMP/check_product_10969.php" `
  "host379076@host379076.hostido.net.pl:domains/ppm.mpptrade.pl/public_html/_TEMP/check_product_10969.php"

if ($LASTEXITCODE -eq 0) {
    Write-Host "[OK] Script uploaded" -ForegroundColor Green
} else {
    Write-Host "[ERROR] Upload failed!" -ForegroundColor Red
    exit 1
}

Write-Host ""
Write-Host "=== RUNNING CHECK SCRIPT ===" -ForegroundColor Cyan
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch `
  "cd domains/ppm.mpptrade.pl/public_html && php _TEMP/check_product_10969.php"

Write-Host ""
Write-Host "=== CHECK COMPLETE ===" -ForegroundColor Green
