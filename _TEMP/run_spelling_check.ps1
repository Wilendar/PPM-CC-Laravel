$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
$RemoteBase = "host379076@host379076.hostido.net.pl:domains/ppm.mpptrade.pl/public_html"

Write-Host "Uploading spelling check script..." -ForegroundColor Cyan
& pscp -i $HostidoKey -P 64321 "_TEMP/check_exact_status_spelling.php" "$RemoteBase/_TEMP/check_exact_status_spelling.php"

Write-Host "`nRunning spelling verification..." -ForegroundColor Cyan
& plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php _TEMP/check_exact_status_spelling.php"
