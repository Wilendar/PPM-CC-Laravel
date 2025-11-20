$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
$RemoteBase = "host379076@host379076.hostido.net.pl:domains/ppm.mpptrade.pl/public_html"

Write-Host "=== TESTING BUG #12 FIX ===" -ForegroundColor Cyan
Write-Host ""

Write-Host "Uploading test script..." -ForegroundColor Gray
& pscp -i $HostidoKey -P 64321 -q "_TEMP/test_bug12_fix.php" "$RemoteBase/_TEMP/test_bug12_fix.php"

Write-Host "Running test..." -ForegroundColor Yellow
& plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php _TEMP/test_bug12_fix.php"

Write-Host ""
