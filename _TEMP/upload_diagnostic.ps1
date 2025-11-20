$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
$RemoteBase = "host379076@host379076.hostido.net.pl:domains/ppm.mpptrade.pl/public_html"

Write-Host "Uploading corrected diagnostic script..." -ForegroundColor Yellow
& pscp -i $HostidoKey -P 64321 "_TEMP/diagnose_bug10_jobs_not_showing.php" "$RemoteBase/_TEMP/diagnose_bug10_jobs_not_showing.php"
Write-Host "Upload complete!" -ForegroundColor Green
