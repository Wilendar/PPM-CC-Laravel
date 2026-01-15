$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"

Write-Host "=== CHECKING STORAGE STRUCTURE ===" -ForegroundColor Cyan

Write-Host "`n[1] Uploading check script..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 "_TEMP/check_storage_structure.php" "host379076@host379076.hostido.net.pl:domains/ppm.mpptrade.pl/public_html/check_storage_structure.php"

Write-Host "[2] Running check..." -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php check_storage_structure.php"

Write-Host "[3] Cleanup..." -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "rm domains/ppm.mpptrade.pl/public_html/check_storage_structure.php"

Write-Host "`n=== DONE ===" -ForegroundColor Green
