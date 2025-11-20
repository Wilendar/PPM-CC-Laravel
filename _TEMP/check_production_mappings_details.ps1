$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"

Write-Host "`n=== PRODUCTION PRICE GROUP MAPPINGS ===" -ForegroundColor Cyan

# Upload diagnostic script
Write-Host "`nUploading diagnostic script..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 "_TEMP/diagnose_bug14_deep_analysis.php" host379076@host379076.hostido.net.pl:domains/ppm.mpptrade.pl/public_html/_TEMP/diagnose_bug14_deep_analysis.php

Write-Host "`nRunning diagnostic on production..." -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php _TEMP/diagnose_bug14_deep_analysis.php"

Write-Host "`nDone!" -ForegroundColor Green
