# Check Block Definitions Table
$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
$RemoteHost = "host379076@host379076.hostido.net.pl"
$RemotePort = "64321"
$RemotePath = "domains/ppm.mpptrade.pl/public_html"

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "Checking Block Definitions" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

# Check if table exists
Write-Host "1. Checking if table exists..." -ForegroundColor Yellow
plink -ssh $RemoteHost -P $RemotePort -i $HostidoKey -batch "cd $RemotePath && php artisan tinker --execute=""echo \Schema::hasTable('block_definitions') ? 'Table EXISTS' : 'Table MISSING';"""

Write-Host ""
Write-Host "2. Counting block definitions..." -ForegroundColor Yellow
plink -ssh $RemoteHost -P $RemotePort -i $HostidoKey -batch "cd $RemotePath && php artisan tinker --execute=""echo App\Models\BlockDefinition::count() . ' block definitions';"""

Write-Host ""
Write-Host "3. Checking BlockGenerator logs..." -ForegroundColor Yellow
plink -ssh $RemoteHost -P $RemotePort -i $HostidoKey -batch "grep -i 'BlockGenerator' $RemotePath/storage/logs/laravel.log | tail -20"

Write-Host ""
Write-Host "4. Checking save errors..." -ForegroundColor Yellow
plink -ssh $RemoteHost -P $RemotePort -i $HostidoKey -batch "grep -i 'save failed\|SQLSTATE\|definition created' $RemotePath/storage/logs/laravel.log | tail -20"

Write-Host ""
Write-Host "========================================" -ForegroundColor Green
Write-Host "Check complete" -ForegroundColor Green
