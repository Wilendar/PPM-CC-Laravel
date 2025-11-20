$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"

Write-Host "Getting full context of debug section..." -ForegroundColor Yellow

& plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && sed -n '30,65p' resources/views/livewire/admin/shops/sync-controller.blade.php"
