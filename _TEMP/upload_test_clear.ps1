$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
$RemoteBase = "host379076@host379076.hostido.net.pl:domains/ppm.mpptrade.pl/public_html"

Write-Host "Uploading test script..." -ForegroundColor Cyan
& pscp -i $HostidoKey -P 64321 "_TEMP/test_clear_with_checkbox.php" "$RemoteBase/_TEMP/test_clear_with_checkbox.php"

Write-Host "`nRunning test (clearAllAges=TRUE)..." -ForegroundColor Cyan
& plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php _TEMP/test_clear_with_checkbox.php"
