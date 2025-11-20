$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"

Write-Host "Checking if clearCacheAndRestartQueue method exists..." -ForegroundColor Yellow

& plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && grep -n 'function clearCacheAndRestartQueue' app/Http/Livewire/Admin/Shops/SyncController.php"
