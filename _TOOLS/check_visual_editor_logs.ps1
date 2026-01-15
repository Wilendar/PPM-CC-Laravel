$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"

plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "tail -n 100 domains/ppm.mpptrade.pl/public_html/storage/logs/laravel.log | grep -i VisualDescriptionEditor"
