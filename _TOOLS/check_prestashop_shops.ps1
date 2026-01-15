# Check PrestaShop shops
$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
$RemoteBase = "domains/ppm.mpptrade.pl/public_html"

$cmd = 'cd ' + $RemoteBase + ' && php artisan tinker --execute="echo json_encode(DB::table(\"prestashop_shops\")->select(\"id\", \"name\", \"url\", \"is_active\")->get());"'

plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch $cmd
