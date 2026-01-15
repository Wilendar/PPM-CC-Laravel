$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
$RemoteBase = "host379076@host379076.hostido.net.pl:domains/ppm.mpptrade.pl/public_html"

Write-Host "=== CLEANUP STUCK PROGRESS ===" -ForegroundColor Cyan

Write-Host "`n[1/3] Uploading script..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 "_TEMP/cleanup_stuck.php" "${RemoteBase}/_TEMP/cleanup_stuck.php"

Write-Host "`n[2/3] Running cleanup..." -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php _TEMP/cleanup_stuck.php"

Write-Host "`n[3/3] Cleanup script..." -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "rm domains/ppm.mpptrade.pl/public_html/_TEMP/cleanup_stuck.php"

Write-Host "`n=== DONE ===" -ForegroundColor Green
