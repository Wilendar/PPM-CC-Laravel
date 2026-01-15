$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"

plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "grep -i 'Imported description\|VisualDescriptionEditor' domains/ppm.mpptrade.pl/public_html/storage/logs/laravel.log | tail -10"
