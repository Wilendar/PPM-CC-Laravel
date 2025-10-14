# SHOP LABELS DEBUG - Add extensive logging
# Purpose: Debug why labels don't disappear and shops are saved incorrectly

$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"

Write-Host "`n=== ENABLING DEBUG LOGGING ===" -ForegroundColor Cyan

# View Laravel logs in real-time
Write-Host "`nTailing Laravel logs..." -ForegroundColor Yellow
Write-Host "Press Ctrl+C to stop" -ForegroundColor Gray

plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && tail -f storage/logs/laravel.log | grep -E 'removeFromShop|addToShops|exportedShops|shopsToRemove'"
