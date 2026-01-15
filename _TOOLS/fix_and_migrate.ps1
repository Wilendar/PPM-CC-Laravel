$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
$LocalBase = "D:\OneDrive - MPP TRADE\Skrypty\PPM-CC-Laravel"
$RemoteBase = "host379076@host379076.hostido.net.pl:domains/ppm.mpptrade.pl/public_html"

Write-Host "=== FIXING TELESCOPE AND RUNNING MIGRATIONS ===" -ForegroundColor Cyan

# 1. Upload fix script
Write-Host "[1/4] Uploading fix script..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 "$LocalBase\_TEMP\fix_telescope.php" "$RemoteBase/fix_telescope.php"

# 2. Run fix script
Write-Host "[2/4] Running fix script..." -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php fix_telescope.php"

# 3. Run migrations
Write-Host "[3/4] Running migrations..." -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php artisan migrate --force"

# 4. Remove fix script
Write-Host "[4/4] Cleaning up..." -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && rm -f fix_telescope.php"

Write-Host "=== DONE ===" -ForegroundColor Green
