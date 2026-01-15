$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
Write-Host "=== CHECKING BATCH/PULL LOGS ===" -ForegroundColor Cyan
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cat domains/ppm.mpptrade.pl/public_html/storage/logs/laravel.log | grep -E 'a08f6e0e|Single product pull|Pulling single|Compatibility imported|importFromPrestaShop' | tail -20"
