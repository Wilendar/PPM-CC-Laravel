$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"

Write-Host "=== UPLOADING VERIFICATION SCRIPT ===" -ForegroundColor Cyan
pscp -i $HostidoKey -P 64321 `
  "_TEMP/verify_conversion.php" `
  "host379076@host379076.hostido.net.pl:domains/ppm.mpptrade.pl/public_html/_TEMP/verify_conversion.php"

Write-Host ""
Write-Host "=== RUNNING VERIFICATION ===" -ForegroundColor Cyan
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch `
  "cd domains/ppm.mpptrade.pl/public_html && php _TEMP/verify_conversion.php"
