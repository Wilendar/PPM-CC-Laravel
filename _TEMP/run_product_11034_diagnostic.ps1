$HostidoKey = 'D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk'
$RemoteBase = 'host379076@host379076.hostido.net.pl:domains/ppm.mpptrade.pl/public_html'

Write-Host '=== UPLOADING DIAGNOSTIC SCRIPT ===' -ForegroundColor Cyan
pscp -i $HostidoKey -P 64321 '_TEMP/check_product_11034_details.php' "$RemoteBase/_TEMP/"

Write-Host "`n=== EXECUTING DIAGNOSTIC ===" -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch 'cd domains/ppm.mpptrade.pl/public_html && php _TEMP/check_product_11034_details.php'

Write-Host "`n=== DONE ===" -ForegroundColor Green
