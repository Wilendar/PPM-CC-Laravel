# Check Laravel logs for shop operations
$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"

Write-Host "`n=== CHECKING LARAVEL LOGS ===" -ForegroundColor Cyan
Write-Host "Looking for recent shop operations..." -ForegroundColor Yellow

# Get last 150 lines of logs and filter
$logs = plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && tail -150 storage/logs/laravel.log"

Write-Host "`nFiltering for shop operations..." -ForegroundColor Yellow

# Display relevant lines
$logs | Select-String -Pattern "removeFromShop|exportedShops|shopsToRemove|Save:|Created ProductShopData" -Context 0,2 | Select-Object -Last 30

Write-Host "`n=== END OF LOGS ===" -ForegroundColor Cyan
