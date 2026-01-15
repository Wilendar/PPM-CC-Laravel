$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"

Write-Host "=== FIXING FEATURE MAPPINGS TABLE ===" -ForegroundColor Cyan

Write-Host "[1/4] Uploading drop script..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 "D:\OneDrive - MPP TRADE\Skrypty\PPM-CC-Laravel\_TEMP\drop_and_migrate.php" "host379076@host379076.hostido.net.pl:domains/ppm.mpptrade.pl/public_html/_TEMP/drop_and_migrate.php"

Write-Host "[2/4] Running drop script..." -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php _TEMP/drop_and_migrate.php"

Write-Host "[3/4] Running migration..." -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php artisan migrate --force"

Write-Host "[4/4] Cleaning up..." -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && rm _TEMP/drop_and_migrate.php && php artisan cache:clear"

Write-Host "=== DONE ===" -ForegroundColor Green
