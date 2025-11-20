$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
$RemoteBase = "host379076@host379076.hostido.net.pl:domains/ppm.mpptrade.pl/public_html"

Write-Host "Uploading..." -ForegroundColor Cyan
& pscp -i $HostidoKey -P 64321 "_TEMP/find_test_autofix_psd.php" "$RemoteBase/_TEMP/find_test_autofix_psd.php"

Write-Host "`nRunning..." -ForegroundColor Cyan
& plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php _TEMP/find_test_autofix_psd.php"
