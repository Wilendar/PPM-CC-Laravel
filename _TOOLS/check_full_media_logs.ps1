# Check full Laravel logs around media sync errors
$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
$RemoteBase = "domains/ppm.mpptrade.pl/public_html"

# Get logs around 09:30 today
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "grep -A 5 'MRF13-68-003' $RemoteBase/storage/logs/laravel.log | tail -n 50"
