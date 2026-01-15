$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"

Write-Host "=== FIXING TELESCOPE MIGRATION ===" -ForegroundColor Cyan

# Mark telescope migration as ran using tinker
Write-Host "[1/2] Marking telescope migration as ran..." -ForegroundColor Yellow
$tinkerCmd = @"
cd domains/ppm.mpptrade.pl/public_html && php artisan tinker --execute="
\$exists = DB::table('migrations')->where('migration', '2025_11_21_221825_create_telescope_entries_table')->exists();
if (!\$exists) {
    DB::table('migrations')->insert(['migration' => '2025_11_21_221825_create_telescope_entries_table', 'batch' => 1]);
    echo 'INSERTED';
} else {
    echo 'EXISTS';
}
"
"@

plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch $tinkerCmd

# Run migrations
Write-Host "[2/2] Running migrations..." -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php artisan migrate --force"

Write-Host "=== DONE ===" -ForegroundColor Green
