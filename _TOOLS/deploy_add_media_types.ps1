$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
$RemoteBase = "host379076@host379076.hostido.net.pl:domains/ppm.mpptrade.pl/public_html"

Write-Host "=== ADDING MEDIA JOB TYPES ===" -ForegroundColor Cyan

Write-Host "`n[1] Uploading script..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 "_TEMP/add_media_types.php" "${RemoteBase}/_TEMP/add_media_types.php"

Write-Host "`n[2] Running script..." -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php _TEMP/add_media_types.php"

Write-Host "`n[3] Cleanup..." -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "rm domains/ppm.mpptrade.pl/public_html/_TEMP/add_media_types.php"

Write-Host "`n=== DONE ===" -ForegroundColor Green
