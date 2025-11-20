$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
$RemoteBase = "host379076@host379076.hostido.net.pl:domains/ppm.mpptrade.pl/public_html"

Write-Host "Uploading diagnostic script..." -ForegroundColor Cyan
& pscp -i $HostidoKey -P 64321 "_TEMP/check_missing_job.php" "$RemoteBase/_TEMP/check_missing_job.php"

Write-Host "`nRunning diagnostic..." -ForegroundColor Cyan
& plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php _TEMP/check_missing_job.php"
