$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"

Write-Host "=== FIXING MIGRATIONS ===" -ForegroundColor Cyan

# First check migration status
Write-Host "[1/3] Checking migration status..." -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php artisan migrate:status 2>&1 | tail -20"

# Mark telescope migration as ran (since table exists)
Write-Host "[2/3] Marking telescope migration as ran..." -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php -r \"DB::table('migrations')->insertOrIgnore(['migration' => '2025_11_21_221825_create_telescope_entries_table', 'batch' => 1]);\" 2>&1 || echo 'Trying artisan...'"

# Run migrations with force
Write-Host "[3/3] Running migrations..." -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php artisan migrate --force 2>&1"

Write-Host "=== DONE ===" -ForegroundColor Green
