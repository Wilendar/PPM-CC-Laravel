$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"

Write-Host "=== UPLOADING DIAGNOSTIC SCRIPT ===" -ForegroundColor Cyan
pscp -i $HostidoKey -P 64321 "_TEMP\diagnose_category_mappings_production.php" host379076@host379076.hostido.net.pl:domains/ppm.mpptrade.pl/public_html/_TEMP/

Write-Host "`n=== RUNNING DIAGNOSIS ===" -ForegroundColor Yellow  
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php _TEMP/diagnose_category_mappings_production.php"
