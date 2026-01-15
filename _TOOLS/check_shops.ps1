# Check PrestaShop shops
$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"

$cmd = "mysql -u host379076_ppm -pqkS4FuXMMDDN4DJhatg6 host379076_ppm -e 'SELECT id, name, url, api_key FROM prestashop_shops;'"

Write-Host "Checking shops..." -ForegroundColor Cyan
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch $cmd
