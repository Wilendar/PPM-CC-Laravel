$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"

Write-Host "`n=== UPLOADING CHECK SCRIPT ===" -ForegroundColor Cyan
pscp -i $HostidoKey -P 64321 "_TEMP/check_pending_data.php" "host379076@host379076.hostido.net.pl:domains/ppm.mpptrade.pl/public_html/_TEMP/check_pending_data.php"

Write-Host "`n=== RUNNING CHECK SCRIPT ===" -ForegroundColor Cyan
$result = plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php _TEMP/check_pending_data.php"

Write-Host $result -ForegroundColor White
