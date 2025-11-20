$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"

Write-Host "Uploading diagnostic script..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 "D:\OneDrive - MPP TRADE\Skrypty\PPM-CC-Laravel\_TEMP\diagnose_bug14_deep_analysis.php" host379076@host379076.hostido.net.pl:domains/ppm.mpptrade.pl/public_html/_TEMP/

Write-Host "`nRunning diagnostic..." -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php _TEMP/diagnose_bug14_deep_analysis.php" | Out-Host

Write-Host "`nDone!" -ForegroundColor Green
